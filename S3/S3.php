<?php

/**
 * proyectoComunidad 
 *
 * @package proyectoComunidad
 */
require_once('vendor/sdk.class.php');
require_once('S3Exceptions.php');

/**
 * S3
 * 
 * Driver para consumir los servicios de la API de Amazon S3
 * 
 * @package CDN
 * @uses AmazonS3 class
 * @used-by CDN class
 * @example http://docs.amazonwebservices.com/AWSSDKforPHP/latest/index.html#m=AmazonS3 Documentacion API Amazon S3
 */
class S3 {

    /**
     * Almacenamos la instancia de AmazonS3
     * 
     * @var object $s3
     */
    private $s3;

    /**
     * Nombre del bucket. 
     * Cambia dependiendo del entorno en el que trabajemos
     * 
     * @var string $bucket
     */
    private $bucket = 'gointegro-devel';

    /**
     * Buckets disponibles en Amazon S3
     * 
     * @var $buckets 
     */
    private $buckets = array(
        'development' => 'gointegro-devel',
        'PRE' => 'gointegro-pre',
        'production' => 'gointegro-pro'
    );

    /**
     * URL base del CDN, desde el cual estaran disponibles publicamente los objetos
     * 
     * @var string $bucketPublicUrl
     */
    private $bucketPublicUrl;

    /**
     * __construct
     * 
     * @param string $bucket
     * @return void
     */
    public function __construct($bucket = '') {

        $this->setBucket($bucket);
    }

    /**
     * connect
     * 
     * Conectamos con la API de Amazon S3
     * 
     * @uses sdk/config.inc.php
     * @return void
     */
    public function connect() {

        try {
            if (!$this->s3 = new AmazonS3()) {
                throw new AuthenticationException('La conexion a Amazon S3 falló');
            }
        } catch (AuthenticationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * setBucketPublicUrl
     * 
     * @return void
     */
    public function setBucketPublicUrl() {

        $this->bucketPublicUrl = 'http://' . $this->getBucket() . '.s3.amazonaws.com/';
    }

    /**
     * getBucketPublicUrl
     * 
     * @return string
     */
    public function getBucketPublicUrl() {

        return $this->bucketPublicUrl;
    }

    /**
     * setBucket
     * 
     * Definimos el bucket con el que se trabajara a a aprtir del valor de la variable de ambiente
     * APPLICATION_ENV, o de la variable $bucket pasada al metodo
     * 
     * @todo estar atentos al cambio del valor para pre de la variable APPLICATION_ENV
     * ya que en breve propusieron cambiarla
     * @throws BuckeOperationException
     * @param string $bucket
     * @return void
     */
    public function setBucket($bucket) {

        try {
            if (is_null(APPLICATION_ENV) && empty($bucket)) {
                throw new BuckeOperationException('No se pudo seleccionar el bucket');
            }
            $this->bucket = !empty($bucket) ? $bucket : $this->buckets[APPLICATION_ENV];
        } catch (BuckeOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * getBucket
     * 
     * @return string
     */
    public function getBucket() {

        try {
            if (!$this->s3->if_bucket_exists($this->bucket)) {
                throw new BuckeOperationException('El bucket que seleccionado no existe');
            }
            return $this->bucket;
        } catch (BuckeOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * getBucketList
     * 
     * @param string $bucket
     * @return array
     */
    public function getBucketList($bucket = '') {

        $response = $this->s3->get_bucket_list($bucket);
        return $response;
    }

    /**
     * createBucket
     * 
     * @param $string $bucket
     * @return array
     */
    public function createBucket($bucket) {

        try {
            $buckets = $this->s3->getBuckets();

            if (in_array($$bucket, $buckets)) {
                throw new Exception('El bucket ya existe');
            }
            $response = $this->s3->create_bucket($bucket);
            return $response;
        } catch (Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * deleteBucket
     * 
     * @param string $bucket
     * @return array
     */
    public function deleteBucket($bucket) {

        try {
            $response = $this->s3->delete_bucket($bucket);
            if (!$response->isOK()) {
                throw new BuckeOperationException('No se pudo eliminar el bucket');
            }
            return $response;
        } catch (Exception $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * cleanBucket
     * 
     * @return array
     */
    public function cleanBucket() {

        if (!$this->s3->if_bucket_exists($this->bucket))
            return false;

        $response = $this->s3->delete_all_objects($this->bucket);

        return $response;
    }

    /**
     * getObjectList
     * 
     * @param string $prefix
     * 
     */
    public function getObjectList($prefix = '') {

        try {
            $response = $this->s3->get_object_list($this->bucket, array(
                'prefix' => $prefix
                    )
            );
            return $response;
        } catch (Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * getObject
     * 
     * @throws ObjectOperationException
     * @param string $object
     * @return array
     */
    public function getObject($object) {

        try {
            if ($this->s3->if_object_exists($this->bucket, $object)) {

                $response = $this->s3->get_object($this->bucket, $object);

                if (!$response->isOK()) {
                    throw new ObjectOperationException('No se pudieron obtener los datos del objeto solicitado');
                }
                return $response;
            }
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * getObjectUrl
     * 
     * @param string $object
     * @return array
     */
    public function getObjectUrl($object) {

        try {
            $response = $this->s3->get_object_url($this->bucket, $object);
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw$e;
        }
    }

    /**
     * downloadObject
     * 
     * Genera la descarga de la imagen
     * 
     * @throws ObjectOperationException
     * @param string $object
     * @return mixed 
     */
    public function downloadObject($object) {

        try {
            $filename = substr(strchr($object, '/'), 1);

            $mimetype = CFMimeTypes::get_mimetype(pathinfo($object, PATHINFO_EXTENSION));

            header("Content-type: " . $mimetype);
            header("Content-Disposition: attachment; filename=" . $filename);

            $response = $this->s3->get_object($this->bucket, $object, array(
                    ));

            if (!$response->isOK()) {
                throw new ObjectOperationException('Error al descargar el objecto');
            }
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * createObjectFromData
     * 
     * Crea un objeto en el bucket de Amazon
     * 
     * @throws ObjectOperationException
     * @param string $object nombre del objeto a almacenar, incluido prefijos por ej. path/subpath/file.png
     * @param string $data datos a almacenar dentro del objeto por ej. 'hola  mundo'
     * @param array $headers
     * @param array $meta
     * @return array
     */
    public function createObjectFromData($object, $data, $headers = array(), $meta = array()) {

        try {

            $response = $this->s3->create_object($this->bucket, $object, array(
                'body' => $data,
                'acl' => AmazonS3::ACL_PUBLIC,
                'headers' => $headers,
                'meta' => $meta
                    )
            );

            if (!$response->isOK()) {
                throw new ObjectOperationException('No se pudo crear el objeto');
            }
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * createObjectFromFile
     * 
     * @throws ObjectOperationException
     * @param string $object
     * @param string $file
     * @param array $headers
     * @param array $meta
     * @return array
     */
    public function createObjectFromFile($object, $file, $headers = array(), $meta = array()) {

        try {
            $mimetype = CFMimeTypes::get_mimetype(pathinfo($object, PATHINFO_EXTENSION));

            $response = $this->s3->create_object($this->bucket, $object, array(
                'fileUpload' => $file,
                'contentType' => $mimetype,
                'acl' => AmazonS3::ACL_PUBLIC,
                'headers' => $headers,
                'meta' => $meta
                    ));

            if (!$response->isOK()) {
                throw new ObjectOperationException('No se pudo crear el objeto');
            }
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * getObjectDetails
     * 
     * @throws ObjectOperationException
     * @param string $object
     * @return array
     */
    public function getObjectDetails($object) {

        try {
            if (!$this->s3->if_object_exists($this->bucket, $object))
                return false;

            $response = $this->s3->get_object_metadata($this->bucket, $object);

            if (!is_array($response)) {
                throw new ObjectOperationException('No se pudo obtener la información del objecto');
            }
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * deleteObject
     * 
     * @throws ObjectOperationException
     * @param string $object
     * @return array 
     */
    public function deleteObject($object) {

        try {

            $response = $this->s3->delete_object($this->bucket, $object);

            if (!$response->isOK()) {
                throw new ObjectOperationException('No se pudo eliminar el objeto');
            }
            return $response;
        } catch (ObjectOperationException $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * __toString
     * 
     * @return string
     */
    public function __toString() {

        return print_r($this->s3, true);
    }

    /**
     * logException
     * 
     * Logueamos los excepciones a syslog tal cual habia solicitado 
     * la gente de infraestrucura de la empresa
     * 
     * @param object $e Exception
     */
    private function logException($e) {

        openlog("error_log", LOG_PID | LOG_PERROR, LOG_LOCAL0);

        syslog(LOG_ERR, 'The Exception is: ' . $e->getMessage() . ' with trace: ' . $e->getTraceAsString());

        closelog();
    }

}

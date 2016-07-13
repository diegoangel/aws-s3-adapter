<?php

/**
 * proyectoComunidad 
 *
 * @package proyectoComunidad
 */
require_once('CDNInterface.php');
require_once('S3/S3.php');

/**
 * CDN
 * 
 * Wrapper que utiliza los nombres y firmas de metodos legacy de la API de Cloudfiles
 * para no tener que cambiar la implementacion en las clases y facilitar
 * la integracion de la API de Amazon S3
 * 
 * @example http://en.wikipedia.org/wiki/Adapter_pattern
 * @package CDN 
 * @uses S3 class
 */
class CDN implements CDNInterface {

    /**
     * Instancia de S3
     * 
     * @var object $cdn instancia de S3
     */
    private $cdn;

    /**
     * En Amazon S3 es el primer nivel de directorios. 
     * En Cloudfiles serian los containers. En Amazon S3 tenemos un solo "container", llamado bucket
     * El nombre del bucket lo obtenemos de un atributo de la instancia S3 publico a traves de un metodo 
     * 
     * @var string $container 
     */
    public $seudoContainer;

    /**
     * URL publica del bucket de Amazon S3
     * 
     * @var string $s3BucketUrl 
     */
    public $s3BucketUrl;

    /**
     * Total de instancias creadas
     * 
     * @static int $instances cantidad de instancias creadas
     * @example http://php.net/manual/es/language.oop5.cloning.php
     */
    static $instances = 0;

    /**
     * Almacenamos el numero de la instancia que se esta ejecutando
     * 
     * @var int $instance numero de la instancia ejecutandose en este momento
     * @example http://php.net/manual/es/language.oop5.cloning.php
     */
    public $instance;

    /**
     * __construct
     * 
     * @return void
     */
    public function __construct() {

        $this->instance = ++self::$instances;

        $this->cdn = new S3;
        $this->cdn->connect();
        $this->cdn->setBucketPublicUrl();

        $this->s3BucketUrl = $this->cdn->getBucketPublicUrl();
    }

    /**
     * set_container
     * 
     * Definimos el nombre del directorio que utilizaremos para subir objetos
     * Esto vendria a ser lo que un container en Cloudfiles
     * Guardamos el nombre del directorio
     * Clonamos el objeto y creamos una nueva instancia y mantenemos la apariencia 
     * de conectarnos a diferentes containers, como se hacia en Cloudfiles
     * 
     * @exampĺe http://php.net/manual/es/language.oop5.cloning.php
     * @param string $name nombre del directorio a utilizar y que representa a un "container" en Amazon S3
     */
    public function set_container($name) {

        $this->seudoContainer = $name;
        $this->cdn_uri = $this->s3BucketUrl . $this->seudoContainer;

        return clone $this;
    }

    /**
     * create_container
     * 
     * En Amazon S3 no se pueden crear buckets adicionales por lo que para suplir esta
     * carencia creamos directorios en el root del bucket que imitaran a los containers de Cloudfiles
     * Como no podemos crear directorios vacios, almacenamos el nombre en el atributo 
     * self::seudoContainer,  en el metodo self::set_container, y luego cuando se crea un objeto se 
     * creara añadiendole este prefijo lo que reflejara la creacion del directorio en Amazon S3
     * 
     * @see self::seudoContainer valor almacenado del prefijo
     * @see self::set_container() almacena el prefijo
     * @param string $name solo presente por compatibilidad con la interface, no se utiliza
     * @return null
     */
    public function create_container($name) {

        return null;
    }

    /**
     * delete_container
     * 
     * @throws ObjectOperationException
     * @param string $name solo presente por compatibilidad con la interface, no se utiliza
     */
    public function delete_container($name) {

        try {

            $objects = $this->cdn->getObjectList($this->seudoContainer);

            if (!empty($objects)) {

                foreach ($objects as $object) {

                    $this->cdn->deleteObject($object);
                }
            }
        } catch (ObjectOperationException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * list_containers
     * 
     * @todo este metodo deberia retornar un array con el primer nivel de directorios de S3
     * @return null
     */
    public function list_containers() {

        return null;
    }

    /**
     * list_container_objects
     * 
     * @return array
     */
    public function list_container_objects() {

        return $this->cdn->getObjectList($this->seudoContainer);
    }

    /**
     * write_file
     * 
     * @param string $file path al archivo
     * @param string $filename nombre del archivo con el que se va a guardar en Amazon S3
     * @param string $filetype solo presente por compatibilidad con la interface, no se utiliza
     */
    public function write_file($file, $filename = null, $filetype = null) {

        $response = $this->cdn->createObjectFromFile(
                $this->seudoContainer . '/' . $filename, fopen($file, 'r'), array(), array()
        );

        if ($response) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * get_object
     * 
     * @param string $file nombre del objeto del cual se desea obtener informacion
     * @return array
     */
    public function get_object($file) {

        return $this->cdn->getObject($file);
    }

    /**
     * getContentType
     * 
     * Este metodo no se utiliza, ya que la clase AmazonS3 ya tiene 
     * un metodo que hace lo mismo. Se conserva para compatibilidad con la interfaz
     * 
     * @param string $filename
     * @return null
     */
    public function getContentType($filename) {

        return null;
    }

    /**
     * deleteObject
     * 
     * Eliminamos el objeto del bucket de Amazon S3
     * 
     * @param string $object nombre del archivo a eliminar
     * @return bool
     */
    public function deleteObject($object) {

        try {
            $response = $this->cdn->deleteObject($this->seudoContainer . '/' . $object);
            return $response->isOK();
        } catch (ObjectOperationException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Manejamos los accesos a los atributos de la clase, definidos o no
     * Solo permitimos el acceso a atributos de la clase publicos 
     * 
     * @trows UnsupportedOperationException
     * @param string $name nombre del atributo al cual se quiere acceder
     * @return mixed
     */
    public function __get($name) {

        try {
            $reflect = new ReflectionClass($this);

            $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            $response = false;

            foreach ($props as $prop) {
                if ($name == $prop->getName()) {
                    $response = $prop->getName();
                }
            }

            if (!$response) {
                throw new UnsupportedOperationException('Propiedad indefinida via __get(): ' . $name);
            }
        } catch (UnsupportedOperationException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Manejamos las llamadas a metodos legacy de Cloudfiles que no definimos
     * 
     * @trows UnsupportedOperationException
     * @param string $name nombre del metodo llamado
     * @param array $args array de argumentos pasados al metodo
     * @return null
     */
    public function __call($name, $args) {

        try {
            throw new UnsupportedOperationException('Llamada al metodo: ' . $name . ' con los argumentos: ' . print_r($args, true));
        } catch (UnsupportedOperationException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Manejamos las llamadas a metodos estaticos legacy de Cloudfiles que no definimos
     * 
     * @trows UnsupportedOperationException
     * @param string $name nombre del metodo llamado
     * @param array $args array de argumentos pasados al metodo
     * @return null
     */
    public static function __callStatic($name, $args) {

        try {
            throw new UnsupportedOperationException('Llamada al metodo: ' . $name . ' con los argumentos: ' . print_r($args, true));
        } catch (UnsupportedOperationException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * __clone
     * 
     * Cuando se clone la instancia se incrementara el numero de instancias creadas
     * y se asignara a self::instance el numero de instancia actual clonada
     * 
     * @example http://php.net/manual/es/language.oop5.cloning.php
     */
    public function __clone() {

        $this->instance = ++self::$instances;
    }

}

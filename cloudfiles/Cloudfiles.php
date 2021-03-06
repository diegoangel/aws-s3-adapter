<?php

// BASIC AUTH INFORMATION
define('USER', 'gerdyzrack');
define('API_KEY', '24dec44a86e93777b1079d3c893e4440');
define('ACCOUNT', NULL);
define('HOST', NULL);

// NEEDED FILES
include_once('vendor/cloudfiles.php');

class Cloudfiles {

    /**
     * auth 
     * Objeto de Autenticacion 
     * @object CF_Authentication 
     * @access public
     */
    public $auth;

    /**
     * conn 
     * Objeto de conneccion 
     * @object CF_Connection
     * @access public
     */
    public $conn;

    public function __construct() {
        $this->auth = new CF_Authentication(USER, API_KEY);
        $this->auth->authenticate();
        $this->conn = new CF_Connection($this->auth);
    }

    public function set_container($name) {
        $this->container = $this->conn->get_container($name);
        // devuelvo la instancia del container para poder usar varios.
        return $this->container;
    }

    public function create_container($name) {
        $this->container = $this->conn->create_container($name);
        return $this->container;
    }

    public function delete_container($name) {
        return $this->conn->delete_container($name);
    }

    public function list_containers() {
        return $this->conn->list_containers();
    }

    public function list_container_objects() {
        if (isset($this->container)) {
            return $this->container->list_objects();
        }
        return false;
    }

    public function write_file($file, $filename = null, $filetype = null) {
        if ($this->container) {
            $container = $this->container;
            if ($filename == null) {
                $filename = basename($file);
            }
            $object = $container->create_object($filename);
            $object->content_type = mime_content_type($file);
            $object->set_etag(md5_file($file));
            $object->content_type = $this->getContentType($filename);
            $file_handle = fopen($file, 'rb');
            $response = $object->write($file_handle);
            fclose($file_handle);
            return $response;
        } else {
            return false;
        }
    }

    public function get_object($file) {
        return $this->container->get_object($file);
    }

    public function getContentType($filename) {

        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

}

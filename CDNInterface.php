<?php
/**
 * proyectoComunidad
 * 
 * @package proyectoComunidad
 */ 
 
/**
 * CDNInterface
 * 
 * Interface que debe implementar CDN para asegurar la compatibilidad con 
 * Cloudfiles
 * 
 * @package CDN
 */
interface CDNInterface {
	
	/**
	 * __construct
	 */ 
    public function __construct();
	
	/**
	 * set_container
	 * 
	 * @param string $name
	 */ 
    public function set_container($name);

	/**
	 * create_container
	 * 
	 * @param string $name
	 */ 
    public function create_container($name);

	/**
	 * delete_container
	 * 
	 * @param string $name
	 */ 
    public function delete_container($name);

	/**
	 * list_containers
	 */ 
    public function list_containers();

	/**
	 * list_container_objects
	 */ 
    public function list_container_objects();

	/**
	 * write_file
	 * 
	 * @param string $file
	 * @param string $filename
	 * @param string $filetype
	 */ 
    public function write_file($file, $filename = null, $filetype = null);
    
	/**
	 * get_object
	 * 
	 * @param string $file
	 */ 
    public function get_object($file);

	/**
	 * getContentType
	 * 
	 * @param string $filename
	 */ 
    public function getContentType($filename);
}

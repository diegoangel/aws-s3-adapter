<?php

 require 'Cloudfiles.php';
 
$dir = '/home/diegorivarola/Public/test/cloudfiles';
 
//mkdir($dir);
//exec('chmod -R 777 ' . $dir);
//exec('chown -R diegorivarola:www-data ' . $dir); 
 
 $cloudfiles = new Cloudfiles();
 
 $containers = $cloudfiles->list_containers();
 
 foreach ($containers as $container) {
	 
	 $localContainer = $dir . '/' . $container;
	 
	 //mkdir($localContainer);
	//exec('chmod -R 777 ' . $localContainer);
	//exec('chown -R diegorivarola:www-data ' . $localContainer); 
	 
	 $containerInstance = $cloudfiles->set_container($container);
	 
	 $objects = $cloudfiles->list_container_objects();
	 
	 foreach ($objects as $object) {
		 
	$file = $containerInstance->get_object($object);
				
		//$filesource = file_get_contents($containerInstance->cdn_uri . '/' . $file->name);
		
		
		$underscoredName = str_replace('/', '--', $file->name);
		
				exec('wget -O ' . $localContainer . '/' . $underscoredName . ' ' . $containerInstance->cdn_uri . '/' . $file->name , $return);
				
				file_put_contents('/tmp/cdn_log_' .$container .'.txt', $underscoredName . ' => ' .$containerInstance->cdn_uri . '/' . $file->name . "\n", FILE_APPEND);
				
				var_dump($return);
		//file_put_contents(str_replace('/', '_', $file->name), $filesource);
		//var_dump($file);
		
		//$file->save_to_filename(str_replace('/', '_', $localContainer . '/' . $file->name));
		var_dump($file->name);
		//die();
	}
}

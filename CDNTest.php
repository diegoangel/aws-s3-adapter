<?php
require 'CDN.php';

define('APPLICATION_ENV', 'development');

$CDN = new CDN;

$container = $CDN->set_container('test');

$container->anyFunction();

$container->zaraza;

echo '<pre>';

var_dump($container->delete_container('test'));

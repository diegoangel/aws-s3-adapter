<?php

# RACKSPACE CDN TEST

require_once 'tests/cloudfiles_ini.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "../");
require_once 'cloudfiles.php';
error_reporting(E_ALL);

$auth = new CF_Authentication(USER,API_KEY);
$auth->authenticate();
$conn = new CF_Connection($auth);

var_dump($auth->export_credentials());

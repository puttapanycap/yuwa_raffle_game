<?php

require _WEBROOT_PATH_ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(_WEBROOT_PATH_);
$dotenv->load();

?>
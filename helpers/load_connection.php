<?php

$db_host = $_ENV['DB_HOSTNAME'];
$db_user = $_ENV['DB_USERNAME'];
$db_pass = $_ENV['DB_PASSWORD'];
$db_db = $_ENV['DB_DATABASE'];

$pdo = new PDO("mysql:host=$db_host;dbname=$db_db;charset=utf8mb4", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>
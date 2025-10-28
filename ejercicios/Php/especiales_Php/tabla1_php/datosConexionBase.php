<?php
$host     = '127.0.0.1';
$port     = '4040';
$dbname   = 'liquidacionesdesueldos';
$user     = 'root';
$password = '';
$charset  = 'utf8mb4';
$localCfg = __DIR__ . '/db_config.local.php';
if (file_exists($localCfg)) {
	include $localCfg; 
}

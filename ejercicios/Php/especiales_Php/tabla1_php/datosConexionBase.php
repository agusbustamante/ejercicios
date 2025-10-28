<?php

$host     = '127.0.0.1';           
$port     = '';                    
$dbname   = 'liquidacionesdesueldos'; 
$user     = 'root';                
$password = '';                    
$charset  = 'utf8mb4';


$local = __DIR__ . '/db_config.local.php';
if (file_exists($local)) {
    require $local;
}

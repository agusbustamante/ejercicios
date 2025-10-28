<?php
// AJUSTES LOCALES (XAMPP)
$DB_NAME = 'liquidacionesdesueldos';
$DB_HOST = '127.0.0.1';     // ¡NO "localhost"! así respetamos el puerto TCP
$DB_PORT = 4040;            // el que ves en phpMyAdmin
$DB_USER = 'root';
$DB_PASS = '';              // por defecto XAMPP

$DSN = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

try {
  $dbh = new PDO($DSN, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  die("ERROR_CONEXION: ".$e->getMessage());
}

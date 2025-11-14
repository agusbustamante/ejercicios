<?php
$host    = "127.0.0.1";
$usuario = "u644169671_2";
$clave   = "Ab20051974";
$base    = "u644169671_liquidaciones1";

try {
    $dbh = new PDO("mysql:host=$host;dbname=$base;charset=utf8mb4", $usuario, $clave);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . "/errores.log", date("Y-m-d H:i") . " | Error conexión: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'DB_CONNECT', 'detalle' => $e->getMessage()]);
    exit;
}
?>
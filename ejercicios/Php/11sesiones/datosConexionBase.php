<?php
$host = "127.0.0.1"; 
$dbname = "u644169671_liquidaciones1";
$user = "u644169671_2";
$password = "Ab20051974"; 

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $dbh = new PDO($dsn, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'DB_CONNECT', 'detalle' => $e->getMessage()]);
    exit;
}
?>

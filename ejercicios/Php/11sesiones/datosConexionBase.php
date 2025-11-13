<?php
$host = "127.0.0.1"; 
$dbname = "u644169671_liquidaciones1";
$user = "root";
$password = "Ab20051974"; 

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $dbh = new PDO($dsn, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die(" Error de conexión: " . $e->getMessage());
}
?>

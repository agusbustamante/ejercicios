<?php
session_start();
function conectarBaseDatos() {
    $host    = "127.0.0.1";
    $usuario = "u644169671_2";
    $clave   = "Ab20051974";
    $base    = "u644169671_liquidaciones1";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$base;charset=utf8mb4", $usuario, $clave);
        return $pdo;
    } catch (PDOException $e) {
        $puntero = fopen(__DIR__ . "/errores.log", "a");
        fwrite($puntero, date("Y-m-d H:i") . " | Error conexión: " . $e->getMessage() . PHP_EOL);
        fclose($puntero);
        exit;
    }
}

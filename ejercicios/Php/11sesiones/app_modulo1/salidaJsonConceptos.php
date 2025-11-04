<?php
/*
 * salidaJsonConceptos.php — Devuelve el listado de conceptos no remunerativos
 *
 * Este servicio responde con un objeto JSON que contiene los
 * conceptos almacenados en la tabla `conceptos_no_remunerativos`. La
 * sesión debe estar iniciada para poder acceder a la información.
 */

session_start();
if (!isset($_SESSION['login'])) {
    header('Location: ../formularioDeLogin.html');
    exit;
}

ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

try {
    $stmt = $dbh->query(
        "SELECT CodigoConcepto, Descripcion
         FROM conceptos_no_remunerativos
         ORDER BY CodigoConcepto"
    );
    $rows = $stmt->fetchAll();
    echo json_encode([
        'conceptos' => $rows,
        'cuenta'    => count($rows)
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    file_put_contents(__DIR__.'/errores.log',
        date('Y-m-d H:i:s') . " CONCEPTOS: " . $e->getMessage() . "\n",
        FILE_APPEND);
    echo json_encode([
        'conceptos' => [],
        'cuenta'    => 0,
        'error'     => 'DB/SQL'
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}
?>
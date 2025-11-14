<?php
/*
 * Devuelve el listado de conceptos no remunerativos.
 */
session_start();
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['conceptos' => [], 'cuenta' => 0, 'error' => 'No autorizado']);
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
         FROM conceptosnoremunerativos
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

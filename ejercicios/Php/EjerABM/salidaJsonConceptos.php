<?php
/*
 * Servicio que devuelve el listado de conceptos no remunerativos.
 * Devuelve un JSON con un array de objetos (CodigoConcepto, Descripcion).
 */
// Evitar que warnings/notices se impriman antes del JSON y redirigirlos al log
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

try {
    $dsn = "mysql:host=$host;" . (!empty($port) ? "port=$port;" : "") . "dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $rows = $pdo->query(
        "SELECT CodigoConcepto, Descripcion
         FROM ConceptosNoRemunerativos
         ORDER BY CodigoConcepto"
    )->fetchAll();
    echo json_encode([
        'conceptos' => $rows,
        'cuenta'    => count($rows)
    ], JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    file_put_contents(__DIR__.'/errores.log',
        date('Y-m-d H:i:s') . " CONCEPTOS: " . $e->getMessage() . "\n",
        FILE_APPEND);
    echo json_encode([
        'conceptos' => [],
        'cuenta'    => 0,
        'error'     => 'DB/SQL'
    ], JSON_INVALID_UTF8_SUBSTITUTE);
}
?>
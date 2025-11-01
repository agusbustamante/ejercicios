<?php
/*
 * Servicio de listado de liquidaciones de sueldos.
 * Recibe parámetros de orden y filtros vía POST y devuelve un JSON.
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

// Columnas permitidas para ordenamiento (nombres exactos de la tabla)
$permitidos = [
    'LegajoEmpleado',
    'ApellidoYNombres',
    'Fecha_liquidacion',
    'MesDeLiquidacion',
    'SueldoBasico',
    'concepto_no_remunerativo_1',
    'Monto_no_remunerativo_1'
];
$rawOrden = $_POST['orden'] ?? 'LegajoEmpleado';

// Filtrar columnas válidas
$parts = array_filter(array_map('trim', explode(',', $rawOrden)));
$validParts = [];
foreach ($parts as $p) {
    if (in_array($p, $permitidos, true)) $validParts[] = $p;
}
$orden = empty($validParts) ? 'LegajoEmpleado' : implode(', ', $validParts);

// Filtro opcional por mes (1-12).  Si no hay filtro, usamos NULL.
$f_mesnum = trim($_POST['f_liquidaciones_mes_num'] ?? '');
$mesParam = ($f_mesnum === '' ? null : (int)$f_mesnum);

try {
    // Seleccionar campos y longitud del PDF.  Devolvemos LENGTH para no enviar el BLOB.
    $sql = "SELECT 
                LegajoEmpleado,
                ApellidoYNombres,
                Fecha_liquidacion,
                MesDeLiquidacion,
                SueldoBasico,
                concepto_no_remunerativo_1,
                Monto_no_remunerativo_1,
                LENGTH(pdf_liquidacion) AS pdf_bytes
            FROM liquidacionesdesueldos
            WHERE ( :mesnum IS NULL OR MONTH(Fecha_liquidacion) = :mesnum )
            ORDER BY $orden";
    $stmt = $dbh->prepare($sql);
    if ($mesParam === null) {
        $stmt->bindValue(':mesnum', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':mesnum', $mesParam, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'liquidaciones' => $rows,
        'cuenta'        => count($rows)
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    file_put_contents(
        __DIR__.'/errores.log',
        date('Y-m-d H:i') . " LISTA: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    echo json_encode([
        'liquidaciones' => [],
        'cuenta'        => 0,
        'error'         => 'DB/SQL'
    ], JSON_INVALID_UTF8_SUBSTITUTE);
}
?>

<?php
/*
 * Servicio de listado de liquidaciones de sueldos.
 * Devuelve JSON con filas sin enviar el BLOB. Incluye pdf_bytes.
 */
session_start();
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['liquidaciones' => [], 'cuenta' => 0, 'error' => 'No autorizado']);
    exit;
}

ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../datosConexionBase.php';

// Columnas permitidas para ordenar
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

// Limpiar y validar columnas
$parts = array_filter(array_map('trim', explode(',', $rawOrden)));
$validParts = [];
foreach ($parts as $p) {
    if (in_array($p, $permitidos, true)) $validParts[] = $p;
}
$orden = empty($validParts) ? 'LegajoEmpleado' : implode(', ', $validParts);

// Filtro por mes (NULL si no aplica)
$f_mesnum = trim($_POST['f_liquidaciones_mes_num'] ?? '');
$mesParam = ($f_mesnum === '' ? null : (int)$f_mesnum);

try {
    $sql = "SELECT
                LegajoEmpleado,
                ApellidoYNombres,
                Fecha_liquidacion,
                MesDeLiquidacion,
                SueldoBasico,
                concepto_no_remunerativo_1,
                Monto_no_remunerativo_1,
                CASE WHEN pdf_liquidacion IS NOT NULL AND LENGTH(pdf_liquidacion) > 0 
                     THEN LENGTH(pdf_liquidacion) 
                     ELSE 0 
                END AS pdf_bytes
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
    http_response_code(500);
    echo json_encode([
        'liquidaciones' => [],
        'cuenta'        => 0,
        'error'         => 'DB/SQL',
        'detalle'       => $e->getMessage()
    ], JSON_INVALID_UTF8_SUBSTITUTE);
}
?>

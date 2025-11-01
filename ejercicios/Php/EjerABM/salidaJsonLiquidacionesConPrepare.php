<?php
/*
 * Servicio de listado de liquidaciones de sueldos.
 * Cumple los apuntes (PDO + prepare + JSON) y registra errores en errores.log
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/datosConexionBase.php';  // <-- crea $dbh (PDO)

/* =============== Orden permitido =============== */
$whitelist = [
  'LegajoEmpleado'             => 'LegajoEmpleado',
  'ApellidoYNombres'           => 'ApellidoYNombres',
  'Fecha_liquidacion'          => 'Fecha_liquidacion',
  'MesDeLiquidacion'           => 'MesDeLiquidacion',
  'SueldoBasico'               => 'SueldoBasico',
  'concepto_no_remunerativo_1' => 'concepto_no_remunerativo_1',
  'Monto_no_remunerativo_1'    => 'Monto_no_remunerativo_1'
];

$rawOrden = $_POST['orden'] ?? 'LegajoEmpleado';
$parts    = array_filter(array_map('trim', explode(',', $rawOrden)));
$valid    = [];
foreach ($parts as $p) {
  if (isset($whitelist[$p])) $valid[] = $whitelist[$p];
}
$orderBy = empty($valid) ? 'LegajoEmpleado' : implode(', ', $valid);

/* =============== Filtro de mes (robusto) =============== */
// Si no viene mes, usamos NULL para que NO aplique filtro.
$f_mesnum = trim($_POST['f_liquidaciones_mes_num'] ?? '');
$mesParam = ($f_mesnum === '' ? null : (int)$f_mesnum);

try {
  /* =============== SQL =============== */
  // NOTA: no enviamos el BLOB en el JSON. Enviamos su tamaño (pdf_bytes) para saber si hay PDF.
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
          ORDER BY $orderBy";

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
    __DIR__ . '/errores.log',
    date('Y-m-d H:i') . " LISTA: " . $e->getMessage() . PHP_EOL,
    FILE_APPEND
  );
  http_response_code(500);
  echo json_encode([
    'liquidaciones' => [],
    'cuenta'        => 0,
    'error'         => 'DB/SQL',
    'detalle'       => $e->getMessage()
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}

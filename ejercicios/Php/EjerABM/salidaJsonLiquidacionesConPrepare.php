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

/* =============== Filtros =============== */
// mes numérico del 1 al 12 (sobre Fecha_liquidacion)
$f_mesnum = $_POST['f_liquidaciones_mes_num'] ?? '';

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
          WHERE (:mesnum = '' OR MONTH(Fecha_liquidacion) = :mesnum)
          ORDER BY $orderBy";

  $stmt = $dbh->prepare($sql);
  if ($f_mesnum === '') {
    $stmt->bindValue(':mesnum', '', PDO::PARAM_STR);
  } else {
    $stmt->bindValue(':mesnum', (int)$f_mesnum, PDO::PARAM_INT);
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
  echo json_encode([
    'liquidaciones' => [],
    'cuenta'        => 0,
    'error'         => 'DB/SQL'
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}

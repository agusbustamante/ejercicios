<?php
/*
 * salidaJsonLiquidacionesConPrepare.php — Servicio de listado de liquidaciones
 *
 * Este servicio obtiene las liquidaciones de sueldos de la tabla
 * `liquidaciones`. Se omite el campo BLOB `pdf_liquidacion` por
 * cuestiones de eficiencia y se devuelve en su lugar la longitud del
 * campo a través de la columna virtual pdf_bytes. Se utiliza un
 * parámetro opcional para filtrar por mes numérico (1..12). La
 * respuesta es un objeto JSON con dos claves: `liquidaciones` y
 * `cuenta`. La sesión debe estar activa.
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

// Limpiar y validar columnas (permitir ordenar por varias separadas por coma)
$parts = array_filter(array_map('trim', explode(',', $rawOrden)));
$validParts = [];
foreach ($parts as $p) {
    if (in_array($p, $permitidos, true)) $validParts[] = $p;
}
$orden = empty($validParts) ? 'LegajoEmpleado' : implode(', ', $validParts);

// Filtro por mes (NULL si no aplica). Desde el cliente se envía
// f_liquidaciones_mes_num; si viene vacío se ignora.
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
                LENGTH(pdf_liquidacion) AS pdf_bytes
            FROM liquidaciones
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
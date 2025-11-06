<?php
/*
 * salidaJsonLiquidacionesConPrepare.php
 *
 * Servicio de listado de liquidaciones de sueldos. Devuelve JSON con
 * filas sin enviar el BLOB. Incluye la cantidad de bytes del PDF
 * mediante la función OCTET_LENGTH para informar si existe o no un
 * documento asociado. Inspirado en salidaJsonLiquidacionesConPrepare.php
 * de 11sesiones y adaptado para nuestra tabla "liquidaciones".
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = conectar();
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
    // Normalizar y validar
    $parts = array_filter(array_map('trim', explode(',', $rawOrden)));
    $validParts = [];
    foreach ($parts as $p) {
        if (in_array($p, $permitidos, true)) {
            $validParts[] = $p;
        }
    }
    $orden = empty($validParts) ? 'LegajoEmpleado' : implode(', ', $validParts);
    // Construir consulta
    $sql = "SELECT
                LegajoEmpleado,
                ApellidoYNombres,
                Fecha_liquidacion,
                MesDeLiquidacion,
                SueldoBasico,
                concepto_no_remunerativo_1,
                Monto_no_remunerativo_1,
                OCTET_LENGTH(pdf_liquidacion) AS pdf_bytes
            FROM liquidaciones
            ORDER BY $orden";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    echo json_encode([
        'liquidaciones' => $rows,
        'cuenta'        => count($rows)
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'liquidaciones' => [],
        'cuenta'        => 0,
        'error'         => 'DB/SQL'
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}
?>
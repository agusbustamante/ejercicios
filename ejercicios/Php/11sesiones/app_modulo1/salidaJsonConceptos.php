<?php
/*
 * salidaJsonConceptos.php
 *
 * Devuelve un catálogo fijo de conceptos no remunerativos en formato
 * JSON. Este script se consulta desde el cliente para llenar el
 * desplegable de conceptos. Se exige sesión activa para evitar
 * accesos anónimos. Inspirado en salidaJsonConceptos.php de
 * 11sesiones pero utilizando datos en memoria en lugar de una tabla.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

$conceptos = [
    ['codigo' => '01', 'descripcion' => 'Indemnizaciones'],
    ['codigo' => '02', 'descripcion' => 'Asignaciones familiares'],
    ['codigo' => '03', 'descripcion' => 'Viáticos'],
    ['codigo' => '04', 'descripcion' => 'Adicional por presentismo'],
];

echo json_encode(['conceptos' => $conceptos], JSON_UNESCAPED_UNICODE);
?>
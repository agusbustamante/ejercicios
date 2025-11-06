<?php
/*
 * pdf.php
 *
 * Devuelve el PDF almacenado en la base codificado en Base64. No
 * expone rutas del servidor ni permite leer archivos arbitrarios. Si
 * no existe un PDF registrado para el legajo solicitado, se
 * responde con un estado de error. Adaptado del comportamiento de
 * leer_pdf.php de nuestro módulo.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

$legajo = $_GET['legajo'] ?? '';
if ($legajo === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta legajo']);
    exit;
}
try {
    $pdo = conectar();
    $stmt = $pdo->prepare('SELECT pdf_liquidacion FROM liquidaciones WHERE LegajoEmpleado = :legajo');
    $stmt->execute([':legajo' => $legajo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['pdf_liquidacion'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'PDF no encontrado']);
        exit;
    }
    $pdfBin = $row['pdf_liquidacion'];
    $base64 = base64_encode($pdfBin);
    echo json_encode(['ok' => true, 'base64' => $base64]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
<?php
session_start();
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    die('No autorizado');
}

require __DIR__.'/../datosConexionBase.php';

$legajo = $_GET['legajo'] ?? '';
if ($legajo === '') {
    http_response_code(400);
    die('Falta legajo');
}

try {
    $stmt = $dbh->prepare("SELECT pdf_liquidacion FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
    $stmt->bindValue(':legajo', $legajo);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        http_response_code(404);
        die('Legajo no encontrado');
    }
    
    if (empty($row['pdf_liquidacion'])) {
        http_response_code(404);
        die('No hay PDF registrado para este legajo');
    }
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    // Headers correctos para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="liquidacion-'.$legajo.'.pdf"');
    header('Content-Length: ' . strlen($row['pdf_liquidacion']));
    header('Cache-Control: private, no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Enviar el PDF directamente
    echo $row['pdf_liquidacion'];
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error del servidor');
}
?>

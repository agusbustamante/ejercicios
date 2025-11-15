<?php
session_start();
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    header('Content-Type: text/plain');
    echo 'No autorizado';
    exit;
}

require __DIR__.'/../datosConexionBase.php';

$legajo = $_GET['legajo'] ?? '';
if ($legajo === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Falta legajo';
    exit;
}

try {
    $stmt = $dbh->prepare("SELECT pdf_liquidacion FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
    $stmt->bindValue(':legajo', $legajo);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Legajo no encontrado';
        exit;
    }
    
    if (empty($row['pdf_liquidacion'])) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'No hay PDF registrado para este legajo';
        exit;
    }
    
    // Headers para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="liquidacion-'.$legajo.'.pdf"');
    header('Content-Length: ' . strlen($row['pdf_liquidacion']));
    header('Cache-Control: no-cache, must-revalidate');
    
    echo $row['pdf_liquidacion'];
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error del servidor';
}
?>

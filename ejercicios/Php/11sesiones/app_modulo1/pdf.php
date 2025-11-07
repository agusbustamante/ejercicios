<?php
/*
 * Servicio para obtener el PDF asociado a un legajo.
 * Busca en la tabla y devuelve el binario. Usa $dbh.
 */
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__.'/errores.log');
require __DIR__.'/datosConexionBase.php';

$legajo = $_GET['legajo'] ?? '';
if ($legajo === '') {
    http_response_code(400);
    echo 'Falta legajo';
    exit;
}
try {
    $stmt = $dbh->prepare(
        "SELECT pdf_liquidacion FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo"
    );
    $stmt->bindValue(':legajo', $legajo);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['pdf_liquidacion'])) {
        http_response_code(404);
        echo 'PDF no encontrado';
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="liquidacion-'.$legajo.'.pdf"');
    echo $row['pdf_liquidacion'];
} catch (Throwable $e) {
    file_put_contents(
        __DIR__.'/errores.log',
        date('Y-m-d H:i')." PDF: ".$e->getMessage()."\n",
        FILE_APPEND
    );
    http_response_code(500);
    echo 'DB/SQL';
}
?>

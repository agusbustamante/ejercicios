<?php
/*
 * Baja de liquidaciones. Elimina por legajo y devuelve JSON con
 * estado detallado. Usa el PDO global $dbh.
 */
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__ . '/errores.log');
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

$respuesta_estado = 'BAJA – inicio';

try {
    $legajo = trim($_POST['LegajoEmpleado'] ?? '');
    $respuesta_estado .= "<br />Código recibido para borrar: $legajo";
    $respuesta_estado .= "<br />conexión exitosa";

    $sql = "DELETE FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";
    $stmt->bindValue(':legajo', $legajo);
    $respuesta_estado .= "<br />bind exitoso";
    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";
    if ($stmt->rowCount() === 0) {
        $respuesta_estado .= "<br />Atención: no se encontró el registro";
    }
    echo json_encode([
        'ok'     => true,
        'estado' => $respuesta_estado
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    file_put_contents(__DIR__.'/errores.log',
        date('Y-m-d H:i')." BAJA: ".$e->getMessage()."\n",
        FILE_APPEND
    );
    $respuesta_estado .= "<br />error: " . $e->getMessage();
    echo json_encode([
        'ok'     => false,
        'error'  => 'DB/SQL',
        'estado' => $respuesta_estado
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}
?>

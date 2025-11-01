<?php
/*
 * Script de baja (eliminación) de una liquidación de sueldos.
 * Recibe el legajo del empleado vía POST y elimina el registro
 * correspondiente. Devuelve un estado detallado en formato JSON.
 */
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

// Inicializar estado
$respuesta_estado = "BAJA – inicio";

try {
    /* 1) Obtener legajo */
    $legajo = trim($_POST['LegajoEmpleado'] ?? '');
    $respuesta_estado .= "<br />Código recibido para borrar: $legajo";
    $respuesta_estado .= "<br />conexión exitosa";

    /* 2) Preparar DELETE */
    $sql  = "DELETE FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    /* 3) Vincular parámetro */
    $stmt->bindValue(':legajo', $legajo);
    $respuesta_estado .= "<br />bind exitoso";

    /* 4) Ejecutar */
    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";
    $filas = $stmt->rowCount();
    if ($filas === 0) {
        $respuesta_estado .= "<br />Atención: no se encontró el registro";
    }

    /* 5) Respuesta JSON */
    echo json_encode([
        'ok'     => true,
        'estado' => $respuesta_estado
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Registrar error
    file_put_contents(
        __DIR__.'/errores.log',
        date('Y-m-d H:i') . " BAJA: " . $e->getMessage() . "\n",
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

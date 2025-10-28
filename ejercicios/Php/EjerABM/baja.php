<?php
/*
 * Script de baja (eliminación) de una liquidación de sueldos.
 * Recibe LegajoEmpleado por POST y elimina el registro.
 * Usa PDO prepare/bind/execute y construye $respuesta_estado para las alertas.
 */
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__.'/errores.log');
header('Content-Type: application/json; charset=utf-8');

require __DIR__.'/datosConexionBase.php'; // crea $dbh (PDO)

$respuesta_estado = "BAJA – inicio";

try {
  /* 1) Leer entrada */
  $legajo = trim($_POST['LegajoEmpleado'] ?? '');
  $respuesta_estado .= "<br />Código recibido para borrar: $legajo";

  /* 2) Conexión ya creada */
  $respuesta_estado .= "<br />conexión exitosa";

  /* 3) Preparar DELETE */
  $sql  = "DELETE FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo";
  $stmt = $dbh->prepare($sql);
  $respuesta_estado .= "<br />preparación exitosa";

  /* 4) Bind */
  $stmt->bindValue(':legajo', $legajo);
  $respuesta_estado .= "<br />bind exitoso";

  /* 5) Ejecutar */
  $stmt->execute();
  $respuesta_estado .= "<br />ejecución exitosa";

  $filas = $stmt->rowCount();
  if ($filas === 0) {
    $respuesta_estado .= "<br />Atención: no se encontró el registro";
  }

  /* 6) Respuesta JSON */
  echo json_encode([
    'ok'     => true,
    'estado' => $respuesta_estado
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  /* Log de error */
  file_put_contents(
    __DIR__.'/errores.log',
    date('Y-m-d H:i')." BAJA: ".$e->getMessage().PHP_EOL,
    FILE_APPEND
  );
  $respuesta_estado .= "<br />error: ".$e->getMessage();

  echo json_encode([
    'ok'     => false,
    'error'  => 'DB/SQL',
    'estado' => $respuesta_estado
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}

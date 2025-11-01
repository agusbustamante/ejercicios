<?php
/*
 * Script de alta de liquidaciones de sueldos.
 * Inserta un nuevo registro (PDO + prepare + bind).
 * Si llega un PDF en $_FILES['pdf_liquidacion'], luego hace un UPDATE para guardar el binario.
 */
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__.'/errores.log');
header('Content-Type: application/json; charset=utf-8');

require __DIR__.'/datosConexionBase.php'; // crea $dbh (PDO)

$respuesta_estado = "ALTA – inicio";

// 1) Recuperar datos del formulario
$legajo   = trim($_POST['LegajoEmpleado'] ?? '');
$apynom   = trim($_POST['ApellidoYNombres'] ?? '');
$fecha    = trim($_POST['Fecha_liquidacion'] ?? '');
$mes      = trim($_POST['MesDeLiquidacion'] ?? '');
$sueldo   = trim($_POST['SueldoBasico'] ?? '0');
$concepto = trim($_POST['CodConceptoNoRem'] ?? ''); // viene del select
$montoNR1 = trim($_POST['Monto_no_remunerativo_1'] ?? '0');

// Registrar entradas (debug requerido)
$respuesta_estado .= "<br />entrada: $legajo | $apynom | $fecha | $mes | $sueldo | $concepto | $montoNR1";

try {
  // 2) Conexión (ya creada)
  $respuesta_estado .= "<br />conexión exitosa";

  // 3) INSERT sin PDF
  $sql = "INSERT INTO liquidacionesdesueldos
            (LegajoEmpleado, ApellidoYNombres, Fecha_liquidacion,
             MesDeLiquidacion, SueldoBasico, concepto_no_remunerativo_1,
             Monto_no_remunerativo_1)
          VALUES
            (:legajo, :apynom, :fecha, :mes, :sueldo, :concepto, :monto)";
  $stmt = $dbh->prepare($sql);
  $respuesta_estado .= "<br />preparación exitosa";

  // 4) Bind
  $stmt->bindValue(':legajo',   $legajo);
  $stmt->bindValue(':apynom',   $apynom);
  $stmt->bindValue(':fecha',    $fecha);
  $stmt->bindValue(':mes',      $mes);
  $stmt->bindValue(':sueldo',   $sueldo);
  $stmt->bindValue(':concepto', $concepto);
  $stmt->bindValue(':monto',    $montoNR1);
  $respuesta_estado .= "<br />bind exitoso";

  // 5) Ejecutar
  $stmt->execute();
  $respuesta_estado .= "<br />ejecución exitosa";

  // 6) Segunda etapa: PDF (opcional)
  if (isset($_FILES['pdf_liquidacion']) && is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'])) {
    $pdfBin   = file_get_contents($_FILES['pdf_liquidacion']['tmp_name']);
    $pdfBytes = filesize($_FILES['pdf_liquidacion']['tmp_name']);

    $sql2 = "UPDATE liquidacionesdesueldos
                SET pdf_liquidacion = :pdf
              WHERE LegajoEmpleado = :legajo";
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->bindParam(':pdf', $pdfBin, PDO::PARAM_LOB);
    $stmt2->bindValue(':legajo', $legajo);
    $stmt2->execute();

    $respuesta_estado .= "<br />registro documento PDF: SI ($pdfBytes bytes)";
  } else {
    $respuesta_estado .= "<br />registro documento PDF: NO";
  }

  // 7) Respuesta JSON (para alert en el cliente)
  echo json_encode([
    'ok'     => true,
    'estado' => $respuesta_estado
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  // Log de error
  file_put_contents(
    __DIR__.'/errores.log',
    date('Y-m-d H:i')." ALTA: ".$e->getMessage().PHP_EOL,
    FILE_APPEND
  );

  $respuesta_estado .= "<br />error: ".$e->getMessage();
  echo json_encode([
    'ok'     => false,
    'error'  => 'DB/SQL',
    'estado' => $respuesta_estado
  ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}

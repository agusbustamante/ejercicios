<?php
/*
 * Script de modificación de liquidaciones de sueldos.
 * Recibe datos vía POST y actualiza los campos simples. Si se adjunta
 * un archivo PDF, realiza una segunda actualización únicamente del campo binario.
 */
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

// Inicializar variable de estado con título para depuración
$respuesta_estado = "MODIFICACIÓN – inicio";

try {
    /* 1) Recuperar datos */
    $legajoOriginal = trim($_POST['LegajoEmpleadoOriginal'] ?? '');
    $legajo         = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom         = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha          = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes            = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo         = trim($_POST['SueldoBasico'] ?? '0');
    $concepto       = trim($_POST['CodConceptoNoRem'] ?? '');
    $montoNR1       = trim($_POST['Monto_no_remunerativo_1'] ?? '0');

    // Registrar entrada con detalles
    $respuesta_estado .= "<br />entrada: orig=$legajoOriginal | nuevo=$legajo | $apynom | $fecha | $mes | $sueldo | $concepto | $montoNR1";
    $respuesta_estado .= "<br />conexión exitosa";

    /* 2) Preparar UPDATE para campos simples */
    $sql = "UPDATE liquidacionesdesueldos
              SET LegajoEmpleado = :legajo,
                  ApellidoYNombres = :apynom,
                  Fecha_liquidacion = :fecha,
                  MesDeLiquidacion = :mes,
                  SueldoBasico = :sueldo,
                  concepto_no_remunerativo_1 = :concepto,
                  Monto_no_remunerativo_1 = :monto
            WHERE LegajoEmpleado = :orig";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    /* 3) Vincular parámetros */
    $stmt->bindValue(':legajo', $legajo);
    $stmt->bindValue(':apynom', $apynom);
    $stmt->bindValue(':fecha', $fecha);
    $stmt->bindValue(':mes', $mes);
    $stmt->bindValue(':sueldo', $sueldo);
    $stmt->bindValue(':concepto', $concepto);
    $stmt->bindValue(':monto', $montoNR1);
    $stmt->bindValue(':orig', $legajoOriginal);
    $respuesta_estado .= "<br />bind exitoso";

    /* 4) Ejecutar UPDATE */
    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";

    /* 5) Actualizar PDF si corresponde */
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

    /* 6) Respuesta JSON */
    echo json_encode([
        'ok'     => true,
        'estado' => $respuesta_estado
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Registrar el error
    file_put_contents(
        __DIR__.'/errores.log',
        date('Y-m-d H:i') . " MODI: " . $e->getMessage() . "\n",
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

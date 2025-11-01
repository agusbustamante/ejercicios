<?php
/*
 * Script de alta de liquidaciones de sueldos.
 * Inserta un nuevo registro en la tabla y, si hay PDF, lo guarda en una segunda operación.
 */
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

// Inicializar variable de estado con título para depuración
$respuesta_estado = "ALTA – inicio";

try {
    /* 1) Recuperar datos del formulario */
    $legajo   = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom   = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha    = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes      = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo   = trim($_POST['SueldoBasico'] ?? '0');
    $concepto = trim($_POST['CodConceptoNoRem'] ?? '');
    $montoNR1 = trim($_POST['Monto_no_remunerativo_1'] ?? '0');

    // Registrar entradas para depuración
    $respuesta_estado .= "<br />entrada: $legajo | $apynom | $fecha | $mes | $sueldo | $concepto | $montoNR1";
    $respuesta_estado .= "<br />conexión exitosa";

    /* 2) Preparar INSERT sin PDF */
    $sql = "INSERT INTO liquidacionesdesueldos
              (LegajoEmpleado, ApellidoYNombres, Fecha_liquidacion,
               MesDeLiquidacion, SueldoBasico, concepto_no_remunerativo_1,
               Monto_no_remunerativo_1)
            VALUES
              (:legajo, :apynom, :fecha, :mes, :sueldo, :concepto, :monto)";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    /* 3) Vincular parámetros */
    $stmt->bindValue(':legajo',  $legajo);
    $stmt->bindValue(':apynom',  $apynom);
    $stmt->bindValue(':fecha',   $fecha);
    $stmt->bindValue(':mes',     $mes);
    $stmt->bindValue(':sueldo',  $sueldo);
    $stmt->bindValue(':concepto',$concepto);
    $stmt->bindValue(':monto',   $montoNR1);
    $respuesta_estado .= "<br />bind exitoso";

    /* 4) Ejecutar INSERT */
    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";

    /* 5) Segunda etapa: actualizar PDF si se adjunta */
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
    // Registrar error en log
    file_put_contents(
        __DIR__ . '/errores.log',
        date('Y-m-d H:i') . " ALTA: " . $e->getMessage() . "\n",
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

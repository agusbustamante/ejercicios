<?php
/*
 * Script de modificación de liquidaciones de sueldos.
 * Recibe datos vía POST y actualiza los campos simples. Si se adjunta
 * un archivo PDF, realiza una segunda actualización únicamente del
 * campo binario. Utiliza un parámetro adicional `LegajoEmpleadoOriginal` para
 * identificar el registro original en caso de que el legajo sea modificado.
 */
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

$respuesta_estado = '';

try {
    /* 1) Conexión PDO */
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $respuesta_estado .= "<br />conexión exitosa";

    /* 2) Recuperar datos */
    $legajoOriginal = trim($_POST['LegajoEmpleadoOriginal'] ?? '');
    $legajo  = trim($_POST['LegajoEmpleado'] ?? '');
    $ap      = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha   = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes     = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo  = trim($_POST['SueldoBasico'] ?? '');
    $concepto= trim($_POST['CodConceptoNoRem'] ?? '');
    $monto   = trim($_POST['Monto_no_remunerativo_1'] ?? '');

    /* 3) Preparar UPDATE para campos simples */
    $sql = "UPDATE liquidacionesdesueldos
              SET LegajoEmpleado = :legajo,
                  ApellidoYNombres = :ap,
                  Fecha_liquidacion = :fecha,
                  MesDeLiquidacion = :mes,
                  SueldoBasico = :sueldo,
                  CodConceptoNoRem = :concepto,
                  Monto_no_remunerativo_1 = :monto
            WHERE LegajoEmpleado = :legajoOriginal";
    $stmt = $pdo->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    /* 4) Vincular parámetros */
    $stmt->bindParam(':legajo',  $legajo);
    $stmt->bindParam(':ap',      $ap);
    $stmt->bindParam(':fecha',   $fecha);
    $stmt->bindParam(':mes',     $mes);
    $stmt->bindParam(':sueldo',  $sueldo);
    $stmt->bindParam(':concepto',$concepto);
    $stmt->bindParam(':monto',   $monto);
    $stmt->bindParam(':legajoOriginal', $legajoOriginal);
    $respuesta_estado .= "<br />bind exitosa";

    /* 5) Ejecutar UPDATE */
    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";

    /* 6) Actualizar PDF si corresponde */
    if (isset($_FILES['pdf_liquidacion']) && is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'])) {
        $pdfContent = file_get_contents($_FILES['pdf_liquidacion']['tmp_name']);
        $sql2 = "UPDATE liquidacionesdesueldos
                   SET pdf_liquidacion = :pdf
                 WHERE LegajoEmpleado = :legajo";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam(':pdf', $pdfContent, PDO::PARAM_LOB);
        $stmt2->bindParam(':legajo', $legajo);
        $stmt2->execute();
        $respuesta_estado .= "<br />Parte registra documento PDF";
    }

    /* 7) Respuesta JSON */
    echo json_encode([
        'estado' => $respuesta_estado,
        'ok'     => true
    ], JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    /* Log de errores */
    $rutaLog = __DIR__.'/errores.log';
    $mensaje = date('Y-m-d H:i') . " MODI: " . $e->getMessage() . "\n";
    file_put_contents($rutaLog, $mensaje, FILE_APPEND);
    $respuesta_estado .= "<br />error: " . $e->getMessage();
    echo json_encode([
        'estado' => $respuesta_estado,
        'ok'     => false,
        'error'  => 'DB/SQL'
    ], JSON_INVALID_UTF8_SUBSTITUTE);
}
?>
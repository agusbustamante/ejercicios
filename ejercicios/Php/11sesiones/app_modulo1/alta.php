<?php
/*
 * alta.php — Alta de liquidaciones de sueldos
 *
 * Este servicio inserta un nuevo registro en la tabla principal
 * `liquidaciones` y, de manera opcional, almacena el documento PDF
 * asociado en la columna binaria `pdf_liquidacion`. Todas las
 * operaciones usan sentencias preparadas PDO para evitar inyecciones
 * SQL. La sesión es obligatoria: si no hay un usuario autenticado
 * redirecciona al formulario de login.
 */

session_start();
// Verificar sesión válida; si no existe redirigir al login
if (!isset($_SESSION['login'])) {
    header('Location: ../formularioDeLogin.html');
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/datosConexionBase.php';

$respuesta_estado = "ALTA – inicio";

try {
    // Datos del formulario
    $legajo   = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom   = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha    = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes      = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo   = trim($_POST['SueldoBasico'] ?? '0');
    // Aceptar ambos nombres desde el form: CodConceptoNoRem o concepto_no_remunerativo_1
    $concepto = trim($_POST['concepto_no_remunerativo_1'] ?? ($_POST['CodConceptoNoRem'] ?? ''));
    $montoNR1 = trim($_POST['Monto_no_remunerativo_1'] ?? '0');

    $respuesta_estado .= "<br />entrada: $legajo | $apynom | $fecha | $mes | $sueldo | $concepto | $montoNR1";
    $respuesta_estado .= "<br />conexión exitosa";

    // Insertar registro base (sin PDF aún)
    $sql = "INSERT INTO liquidaciones
              (LegajoEmpleado, ApellidoYNombres, Fecha_liquidacion,
               MesDeLiquidacion, SueldoBasico, concepto_no_remunerativo_1,
               Monto_no_remunerativo_1)
            VALUES
              (:legajo, :apynom, :fecha, :mes, :sueldo, :concepto, :monto)";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    $stmt->bindValue(':legajo',   $legajo);
    $stmt->bindValue(':apynom',   $apynom);
    $stmt->bindValue(':fecha',    $fecha);
    $stmt->bindValue(':mes',      $mes);
    $stmt->bindValue(':sueldo',   $sueldo);
    $stmt->bindValue(':concepto', $concepto);
    $stmt->bindValue(':monto',    $montoNR1);
    $respuesta_estado .= "<br />bind exitoso";

    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";

    // Registrar PDF si se cargó un archivo
    if (isset($_FILES['pdf_liquidacion']) && is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'])) {
        $pdfBin   = file_get_contents($_FILES['pdf_liquidacion']['tmp_name']);
        $pdfBytes = filesize($_FILES['pdf_liquidacion']['tmp_name']);
        $sql2 = "UPDATE liquidaciones
                    SET pdf_liquidacion = :pdf
                 WHERE LegajoEmpleado = :legajo";
        $stmt2 = $dbh->prepare($sql2);
        // Se usa PARAM_LOB para enviar el binario
        $stmt2->bindParam(':pdf', $pdfBin, PDO::PARAM_LOB);
        $stmt2->bindValue(':legajo', $legajo);
        $stmt2->execute();
        $respuesta_estado .= "<br />registro documento PDF: SI ($pdfBytes bytes)";
    } else {
        $respuesta_estado .= "<br />registro documento PDF: NO";
    }

    echo json_encode([
        'ok'     => true,
        'estado' => $respuesta_estado
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Registrar el error en un log interno
    file_put_contents(
        __DIR__.'/errores.log',
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
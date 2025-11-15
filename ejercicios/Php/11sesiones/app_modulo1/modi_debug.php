<?php
session_start();
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../datosConexionBase.php';

$respuesta_estado = "MODIFICACIÓN – inicio";
$debug_info = [];

try {
    $legajoOriginal = trim($_POST['LegajoEmpleadoOriginal'] ?? '');
    $legajo         = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom         = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha          = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes            = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo         = trim($_POST['SueldoBasico'] ?? '0');
    $concepto       = trim($_POST['concepto_no_remunerativo_1'] ?? ($_POST['CodConceptoNoRem'] ?? ''));
    $montoNR1       = trim($_POST['Monto_no_remunerativo_1'] ?? '0');

    $debug_info[] = "Legajo original: $legajoOriginal";
    $debug_info[] = "Datos recibidos: $apynom, $fecha, $mes, $sueldo, $concepto, $montoNR1";

    // DEBUG: Información del archivo
    if (isset($_FILES['pdf_liquidacion'])) {
        $debug_info[] = "Array \$_FILES presente";
        $debug_info[] = "Archivo nombre: " . ($_FILES['pdf_liquidacion']['name'] ?? 'N/A');
        $debug_info[] = "Archivo tamaño: " . ($_FILES['pdf_liquidacion']['size'] ?? 'N/A');
        $debug_info[] = "Archivo error: " . ($_FILES['pdf_liquidacion']['error'] ?? 'N/A');
        $debug_info[] = "Archivo tmp: " . ($_FILES['pdf_liquidacion']['tmp_name'] ?? 'N/A');
        $debug_info[] = "is_uploaded_file: " . (is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'] ?? '') ? 'SÍ' : 'NO');
    } else {
        $debug_info[] = "NO hay \$_FILES['pdf_liquidacion']";
    }

    $respuesta_estado .= "<br />entrada: orig=$legajoOriginal | nuevo=$legajo | $apynom | $fecha | $mes | $sueldo | $concepto | $montoNR1";
    $respuesta_estado .= "<br />conexión exitosa";

    $sql = "UPDATE liquidacionesdesueldos
              SET ApellidoYNombres = :apynom,
                  Fecha_liquidacion = :fecha,
                  MesDeLiquidacion = :mes,
                  SueldoBasico = :sueldo,
                  concepto_no_remunerativo_1 = :concepto,
                  Monto_no_remunerativo_1 = :monto
            WHERE LegajoEmpleado = :orig";
    $stmt = $dbh->prepare($sql);
    $respuesta_estado .= "<br />preparación exitosa";

    $stmt->bindValue(':apynom',   $apynom);
    $stmt->bindValue(':fecha',    $fecha);
    $stmt->bindValue(':mes',      $mes);
    $stmt->bindValue(':sueldo',   $sueldo);
    $stmt->bindValue(':concepto', $concepto);
    $stmt->bindValue(':monto',    $montoNR1);
    $stmt->bindValue(':orig',     $legajoOriginal);
    $respuesta_estado .= "<br />bind exitoso";

    $stmt->execute();
    $respuesta_estado .= "<br />ejecución exitosa";

    // MANEJO DETALLADO DEL PDF
    if (isset($_FILES['pdf_liquidacion']) && is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'])) {
        $debug_info[] = "Iniciando proceso de PDF...";
        
        $archivoTmp = $_FILES['pdf_liquidacion']['tmp_name'];
        $nombreArchivo = $_FILES['pdf_liquidacion']['name'];
        $tamanoArchivo = $_FILES['pdf_liquidacion']['size'];
        
        $debug_info[] = "Archivo: $nombreArchivo ($tamanoArchivo bytes)";
        
        $pdfBin = file_get_contents($archivoTmp);
        $pdfBytes = filesize($archivoTmp);
        
        $debug_info[] = "Contenido leído: " . ($pdfBin !== false ? "SÍ" : "NO");
        $debug_info[] = "Bytes reales: $pdfBytes";
        
        if ($pdfBytes > 0 && $pdfBin !== false) {
            $debug_info[] = "Archivo válido, iniciando UPDATE...";
            
            // Verificar estado anterior
            $stmtVerif = $dbh->prepare("SELECT LENGTH(pdf_liquidacion) as bytes_antes FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
            $stmtVerif->bindValue(':legajo', $legajoOriginal);
            $stmtVerif->execute();
            $anterior = $stmtVerif->fetch();
            $debug_info[] = "Bytes antes del UPDATE: " . ($anterior['bytes_antes'] ?? 'NULL');
            
            $sql2 = "UPDATE liquidacionesdesueldos
                       SET pdf_liquidacion = :pdf
                     WHERE LegajoEmpleado = :legajo";
            $stmt2 = $dbh->prepare($sql2);
            $stmt2->bindParam(':pdf', $pdfBin, PDO::PARAM_LOB);
            $stmt2->bindValue(':legajo', $legajoOriginal);
            $resultado = $stmt2->execute();
            
            $debug_info[] = "UPDATE ejecutado: " . ($resultado ? "SÍ" : "NO");
            
            if ($resultado) {
                // Verificar estado posterior
                $stmtVerif2 = $dbh->prepare("SELECT LENGTH(pdf_liquidacion) as bytes_despues FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
                $stmtVerif2->bindValue(':legajo', $legajoOriginal);
                $stmtVerif2->execute();
                $posterior = $stmtVerif2->fetch();
                $debug_info[] = "Bytes después del UPDATE: " . ($posterior['bytes_despues'] ?? 'NULL');
                
                $respuesta_estado .= "<br />registro documento PDF: SÍ ($pdfBytes bytes)";
            } else {
                $debug_info[] = "ERROR en UPDATE";
                $errorInfo = $stmt2->errorInfo();
                $debug_info[] = "Error SQL: " . print_r($errorInfo, true);
                $respuesta_estado .= "<br />registro documento PDF: ERROR - fallo UPDATE";
            }
        } else {
            $debug_info[] = "Archivo vacío o error al leer";
            $respuesta_estado .= "<br />registro documento PDF: ERROR - archivo vacío";
        }
    } else {
        $debug_info[] = "No se subió archivo PDF o no es válido";
        $respuesta_estado .= "<br />registro documento PDF: NO";
    }

    // Agregar debug al estado
    $respuesta_estado .= "<br /><br />DEBUG INFO:<br />" . implode("<br />", $debug_info);

    echo json_encode([
        'ok'     => true,
        'estado' => $respuesta_estado,
        'debug'  => $debug_info
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    $debug_info[] = "EXCEPCIÓN: " . $e->getMessage();
    file_put_contents(
        __DIR__.'/errores.log',
        date('Y-m-d H:i') . " MODI: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    $respuesta_estado .= "<br />error: " . $e->getMessage();
    $respuesta_estado .= "<br /><br />DEBUG INFO:<br />" . implode("<br />", $debug_info);
    
    echo json_encode([
        'ok'     => false,
        'error'  => 'DB/SQL',
        'estado' => $respuesta_estado,
        'debug'  => $debug_info
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
}
?>
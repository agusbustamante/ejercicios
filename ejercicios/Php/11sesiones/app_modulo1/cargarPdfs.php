<?php
/*
 * cargarPdfs.php
 *
 * Actualiza el campo pdf_liquidacion de una fila existente. Este
 * script se ejecuta en una segunda etapa tras la inserción o
 * modificación de los datos simples. Valida la existencia del
 * legajo, el tipo de archivo (solo PDF) y un límite de tamaño para
 * evitar subir archivos enormes. Adaptado de la lógica utilizada en
 * actualizar_pdf.php de nuestro módulo.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'estado' => 'Método inválido']);
        exit;
    }
    $legajo = trim($_POST['LegajoEmpleado'] ?? '');
    if ($legajo === '') {
        echo json_encode(['ok' => false, 'estado' => 'Falta legajo']);
        exit;
    }
    if (!isset($_FILES['pdf_liquidacion']) || !is_uploaded_file($_FILES['pdf_liquidacion']['tmp_name'])) {
        echo json_encode(['ok' => false, 'estado' => 'No se envió PDF']);
        exit;
    }
    $tmpName = $_FILES['pdf_liquidacion']['tmp_name'];
    $size    = filesize($tmpName);
    // Limitar a 10 MB para evitar consumos excesivos
    if ($size > 10 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'estado' => 'Archivo PDF demasiado grande']);
        exit;
    }
    // Verificar MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') {
        echo json_encode(['ok' => false, 'estado' => 'El archivo debe ser PDF']);
        exit;
    }
    $pdfBin = file_get_contents($tmpName);
    $pdo    = conectar();
    // Verificar existencia del legajo
    $st = $pdo->prepare('SELECT COUNT(*) FROM liquidaciones WHERE LegajoEmpleado = :leg');
    $st->execute([':leg' => $legajo]);
    if ($st->fetchColumn() == 0) {
        echo json_encode(['ok' => false, 'estado' => 'Legajo no encontrado']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE liquidaciones SET pdf_liquidacion = :pdf WHERE LegajoEmpleado = :leg');
    $stmt->bindParam(':pdf', $pdfBin, PDO::PARAM_LOB);
    $stmt->bindParam(':leg', $legajo);
    $stmt->execute();
    echo json_encode(['ok' => true, 'estado' => 'PDF actualizado con éxito']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'estado' => 'Error: ' . $e->getMessage()]);
}
?>
<?php
// pdf.php: devuelve el PDF guardado en la columna BLOB
// Requiere que datosConexionBase.php cree $dbh (PDO)
require __DIR__ . '/datosConexionBase.php';

header('Content-Type: application/pdf');

$legajo = $_GET['legajo'] ?? '';
if ($legajo === '') {
  http_response_code(400);
  exit('Falta legajo');
}

$stmt = $dbh->prepare(
  "SELECT pdf_liquidacion 
     FROM liquidacionesdesueldos 
    WHERE LegajoEmpleado = ?"
);
$stmt->execute([$legajo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['pdf_liquidacion'])) {
  http_response_code(404);
  exit('PDF no encontrado');
}

header('Content-Disposition: inline; filename="liquidacion-'.$legajo.'.pdf"');
echo $row['pdf_liquidacion'];

<?php
// cargarPdfs.php: carga los PDF locales a la columna BLOB
require __DIR__ . '/../datosConexionBase.php';

$map = [
  'E001' => __DIR__.'/pdfs/liquidacion-01.pdf',
  'E002' => __DIR__.'/pdfs/liquidacion-02.pdf',
  'E003' => __DIR__.'/pdfs/liquidacion-03.pdf',
  'E004' => __DIR__.'/pdfs/liquidacion-04.pdf',
];

$sql  = "UPDATE liquidacionesdesueldos 
            SET pdf_liquidacion = :pdf 
          WHERE LegajoEmpleado = :legajo";
$stmt = $dbh->prepare($sql);

foreach ($map as $legajo => $ruta) {
  if (!is_file($ruta)) {
    echo "No existe: $ruta<br>";
    continue;
  }
  $bin = file_get_contents($ruta);
  $stmt->bindParam(':legajo', $legajo, PDO::PARAM_STR);
  $stmt->bindParam(':pdf',    $bin,    PDO::PARAM_LOB);
  $stmt->execute();
  echo "Cargado PDF para $legajo<br>";
}
echo "Listo.";

<?php
header('Content-Type: application/json; charset=utf-8');
require 'datosConexionBase.php';

try {
  $dsn = "mysql:host=$host;".(!empty($port)?"port=$port;":"")."dbname=$dbname;charset=$charset";
  $pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);

  $rows = $pdo->query(
    "SELECT CodigoConcepto, Descripcion
     FROM ConceptosNoRemunerativos
     ORDER BY CodigoConcepto"
  )->fetchAll();

  echo json_encode(['conceptos'=>$rows, 'cuenta'=>count($rows)]);
}
catch (Throwable $e) {
  file_put_contents(__DIR__.'/errores.log',
    date('Y-m-d H:i:s')." CONCEPTOS: ".$e->getMessage()."\n",
    FILE_APPEND);
  echo json_encode(['conceptos'=>[], 'cuenta'=>0, 'error'=>'DB/SQL']);
}

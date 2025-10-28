<?php
header('Content-Type: application/json; charset=utf-8');
require 'datosConexionBase.php';

try{
  $pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
    $user, $password,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
  );

  // Trae valores únicos de MesDeLiquidacion (formato que uses: 'YYYY-MM' o texto)
  $stmt = $pdo->query(
    "SELECT DISTINCT MesDeLiquidacion
       FROM LiquidacionesDeSueldos
      WHERE MesDeLiquidacion IS NOT NULL AND MesDeLiquidacion <> ''
      ORDER BY MesDeLiquidacion DESC"
  );
  $meses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['meses'=>$meses, 'cuenta'=>count($meses)]);
}catch(Throwable $e){
  $fp = fopen(__DIR__.'/errores.log','a');
  fwrite($fp, date('Y-m-d H:i')." MESES: ".$e->getMessage()."\n");
  fclose($fp);
  echo json_encode(['meses'=>[], 'cuenta'=>0, 'error'=>'DB/SQL']);
}

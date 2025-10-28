<?php
header('Content-Type: application/json; charset=utf-8');
require 'datosConexionBase.php';

$permitidos = [
  'LegajoEmpleado','ApellidoYNombres','Fecha_liquidacion','MesDeLiquidacion',
  'SueldoBasico','CodConceptoNoRem','Monto_no_remunerativo_1'
];
$rawOrden = $_POST['orden'] ?? 'LegajoEmpleado';

$parts = array_filter(array_map('trim', explode(',', $rawOrden)));
$validParts = [];
foreach ($parts as $p) {
  if (in_array($p, $permitidos, true)) $validParts[] = $p;
}
if (empty($validParts)) {
  $orden = 'LegajoEmpleado';
} else {
  $orden = implode(', ', $validParts);
}

$f_legajo   = trim($_POST['f_liquidaciones_legajoEmpleado'] ?? '');
$f_apellido = trim($_POST['f_liquidaciones_apellidoYNombres'] ?? '');
$f_fecha    = $_POST['f_liquidaciones_fecha_liquidacion'] ?? '';
$f_mesnum   = $_POST['f_liquidaciones_mes_num'] ?? '';  
$f_sueldo   = $_POST['f_liquidaciones_sueldoBasico_min'] ?? '';
$f_conc     = $_POST['f_liquidaciones_concepto'] ?? '';
$f_monto    = $_POST['f_liquidaciones_monto_no_remunerativo_1_min'] ?? '';

try{
  /* 3) Conexión PDO */
  $pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
    $user, $password,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
  );

  $sql = "SELECT LegajoEmpleado, ApellidoYNombres, Fecha_liquidacion, MesDeLiquidacion,
                 SueldoBasico, CodConceptoNoRem, Monto_no_remunerativo_1, pdf_liquidacion
          FROM liquidacionesdesueldos
          WHERE LegajoEmpleado   LIKE CONCAT('%', :legajo, '%')
            AND ApellidoYNombres LIKE CONCAT('%', :ap, '%')
            AND (:fecha  = '' OR Fecha_liquidacion = :fecha)
            AND (:mesnum = '' OR MONTH(Fecha_liquidacion) = :mesnum)
            AND (:conc   = '' OR CodConceptoNoRem = :conc)
            AND (:sueldo = '' OR SueldoBasico >= :sueldo)
            AND (:monto  = '' OR Monto_no_remunerativo_1 >= :monto)
          ORDER BY $orden";

  $stmt = $pdo->prepare($sql);

  $stmt->bindParam(':legajo', $f_legajo);
  $stmt->bindParam(':ap',     $f_apellido);
  $stmt->bindParam(':fecha',  $f_fecha);
  $stmt->bindParam(':conc',   $f_conc);


  if ($f_mesnum === '') { $stmt->bindValue(':mesnum', '', PDO::PARAM_STR); }
  else                  { $stmt->bindValue(':mesnum', (int)$f_mesnum, PDO::PARAM_INT); }

  if ($f_sueldo === '') { $stmt->bindValue(':sueldo', '', PDO::PARAM_STR); }
  else                  { $stmt->bindValue(':sueldo', $f_sueldo); }

  if ($f_monto  === '') { $stmt->bindValue(':monto',  '', PDO::PARAM_STR); }
  else                  { $stmt->bindValue(':monto',  $f_monto); }

  /* 6) Ejecutar y devolver */
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['liquidaciones'=>$rows, 'cuenta'=>count($rows)]);

}catch(Throwable $e){
  /* 7) Log de errores */
  $fp = fopen(__DIR__.'/errores.log','a');
  fwrite($fp, date('Y-m-d H:i')." LIQUIDACIONES: ".$e->getMessage()."\n");
  fclose($fp);
  echo json_encode(['liquidaciones'=>[], 'cuenta'=>0, 'error'=>'DB/SQL']);
}

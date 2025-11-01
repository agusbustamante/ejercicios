<?php


ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errores.log');

function cfg(): array {
  $is_hostinger = false;
  $hhost = $_SERVER['HTTP_HOST'] ?? '';
  $sname = $_SERVER['SERVER_NAME'] ?? '';
  if (stripos($hhost, 'hostinger') !== false || stripos($hhost, 'hostingersite') !== false || stripos($sname, 'hostinger') !== false) {
    $is_hostinger = true;
  }

  if ($is_hostinger) {
    return [
      // Configuración de Hostinger
      'host'     => 'u644169671_liquidaABM',       
      'port'     => 3306,
      'dbname'   => 'u644169671_liquidaABM',    
      'user'     => 'u644169671_usuario',       
      'password' => 'Ab20051974',
      'charset'  => 'utf8mb4',
    ];
  }

  // ==== Configuracion XAMPP ====
  return [
    'host'     => '127.0.0.1',
    'port'     => 4040,                  
    'dbname'   => 'liquidacionesdesueldos',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
  ];
}

$_CFG = cfg();

$DSN = "mysql:host={$_CFG['host']};port={$_CFG['port']};dbname={$_CFG['dbname']};charset={$_CFG['charset']}";

try {
  $dbh = new PDO($DSN, $_CFG['user'], $_CFG['password'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  // Log y respuesta clara (JSON) para que el front muestre el alerta de depuración
  file_put_contents(
    __DIR__ . '/errores.log',
    date('Y-m-d H:i') . " CONEXION_FALLIDA: " . $e->getMessage() . PHP_EOL,
    FILE_APPEND
  );

  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'error'   => 'DB_CONNECT',
    'detalle' => $e->getMessage(),
  ]);
  exit; 
}

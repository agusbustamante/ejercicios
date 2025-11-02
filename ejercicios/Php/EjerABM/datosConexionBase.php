<?php
/*
 * datosConexionBase.php — único punto de conexión PDO para la aplicación.
 * Detecta ambiente Hostinger o local y ajusta las credenciales.
 */
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__.'/errores.log');

function cfg(): array {
    $is_hostinger = false;
    $hhost = $_SERVER['HTTP_HOST'] ?? '';
    $sname = $_SERVER['SERVER_NAME'] ?? '';
    if (stripos($hhost,'hostinger')!==false || stripos($hhost,'hostingersite')!==false ||
        stripos($sname,'hostinger')!==false) {
        $is_hostinger = true;
    }
    if ($is_hostinger) {
        return [
            'host'     => 'auth-db2021.hstgr.io',             
            'port'     => 3306,
            'dbname'   => 'u644169671_liquidaABM',
            'user'     => 'u644169671_usuario2',
            'password' => 'Ab20051974',
            'charset'  => 'utf8mb4',
        ];
    }
    // Local
    return [
        'host'     => '127.0.0.1',
        'port'     => 4040,
        'dbname'   => 'liquidacionesdesueldos',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ];
}

$_CFG   = cfg();
$host    = $_CFG['host'];
$port    = $_CFG['port'];
$dbname  = $_CFG['dbname'];
$user    = $_CFG['user'];
$password= $_CFG['password'];
$charset = $_CFG['charset'];
$DSN     = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $dbh = new PDO($DSN, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    file_put_contents(__DIR__.'/errores.log',
        date('Y-m-d H:i')." CONEXION_FALLIDA: ".$e->getMessage()."\n",
        FILE_APPEND
    );
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error'  => 'DB_CONNECT',
        'detalle'=> $e->getMessage(),
    ]);
    exit;
}
?>



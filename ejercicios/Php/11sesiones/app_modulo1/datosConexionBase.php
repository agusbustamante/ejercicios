<?php

ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log', __DIR__.'/errores.log');

function cfg(): array {
    $is_hostinger = false;
    $hhost = $_SERVER['HTTP_HOST'] ?? '';
    $sname = $_SERVER['SERVER_NAME'] ?? '';

    $force_hostinger_env = getenv('FORCE_HOSTINGER');
    $force_hostinger_file = file_exists(__DIR__ . '/use_hostinger');
    if ($force_hostinger_env === '1' || $force_hostinger_file) {
        file_put_contents(__DIR__ . '/errores.log', date('Y-m-d H:i') . " FORZAR_HOSTINGER activo\n", FILE_APPEND);
        $is_hostinger = true;
    } else {
        if (stripos($hhost,'hostinger')!==false || stripos($hhost,'hostingersite')!==false ||
            stripos($sname,'hostinger')!==false) {
            $is_hostinger = true;
        }
    }
    if ($is_hostinger) {
        return [
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'dbname'   => 'u644169671_liquidaciones1',
            'user'     => 'u644169671_2',
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


if (isset($_CFG['host']) && (in_array($_CFG['host'], ['127.0.0.1','localhost'], true) || ($_CFG['user'] ?? '') === 'root')) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
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

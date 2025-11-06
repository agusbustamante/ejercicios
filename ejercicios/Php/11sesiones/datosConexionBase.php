<?php
/*
 * datosConexionBase.php
 *
 * Punto único de conexión a la base de datos y utilidades de sesión. Este
 * archivo se inspira en datosConexionBase.php de 11sesiones pero se
 * simplifica para este módulo. Se define una función conectar() que
 * devuelve una instancia PDO configurada y funciones de verificación de
 * sesión para scripts en la raíz y dentro de app_modulo1.
 */

// Iniciar la sesión si aún no se ha hecho. Esto permite acceder a
// $_SESSION en cualquier script que incluya este archivo. La función
// session_status() se utiliza para no llamar session_start() varias
// veces, lo cual provocaría advertencias.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve un objeto PDO listo para usarse. Ajuste estos parámetros
 * según su entorno de base de datos. Se utiliza una conexión
 * persistente en memoria (static $pdo) para evitar reabrir la
 * conexión en cada llamada.
 *
 * @return PDO
 */
function conectar() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    // Credenciales locales por defecto. Modifique estos valores según
    // corresponda en su entorno de despliegue.
    $host    = '127.0.0.1';
    $port    = 3306;
    $dbname  = 'modulo1db';
    $user    = 'root';
    $pass    = '';
    $charset = 'utf8mb4';
    $dsn     = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

/**
 * Verifica que la sesión de usuario esté activa para scripts ubicados en
 * la raíz del proyecto. Si no hay sesión válida, redirige al
 * formulario de login en la misma carpeta.
 */
function checkSession() {
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['login'])) {
        header('Location: formularioDeLogin.html');
        exit;
    }
}

/**
 * Verifica que la sesión de usuario esté activa para scripts dentro
 * de la carpeta app_modulo1. Si no hay sesión válida, redirige al
 * formulario de login ubicado en la carpeta superior.
 */
function checkSessionApp() {
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['login'])) {
        header('Location: ../formularioDeLogin.html');
        exit;
    }
}
?>
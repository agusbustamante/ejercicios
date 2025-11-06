<?php
/*
 * ingresoAlSistema.php
 *
 * Procesa las credenciales enviadas desde formularioDeLogin.html. Se
 * valida el usuario y la clave (SHA‑256), se incrementa el
 * contador de sesión la primera vez que se establece la sesión y se
 * persiste en la tabla usuarios. Inspirado en ingresoAlSistema.php de
 * 11sesiones y adaptado a la estructura de tabla (id, login,
 * apellido_nombres, password_hash, contador_sesion).
 */

session_start();
require_once __DIR__ . '/datosConexionBase.php';

// Solo aceptar peticiones POST provenientes del formulario de login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formularioDeLogin.html');
    exit;
}

$login = trim($_POST['login'] ?? '');
$clave = trim($_POST['clave'] ?? '');

// Validar campos obligatorios
if ($login === '' || $clave === '') {
    header('Location: formularioDeLogin.html?e=1');
    exit;
}

try {
    $pdo = conectar();
    // Buscar usuario por login
    $stmt = $pdo->prepare('SELECT id, login, apellido_nombres, password_hash, contador_sesion FROM usuarios WHERE login = :login LIMIT 1');
    $stmt->execute([':login' => $login]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    // Calcular el hash de la clave en el mismo formato que en la base
    $claveHash = hash('sha256', $clave);
    if (!$usuario || strtolower($usuario['password_hash']) !== strtolower($claveHash)) {
        // Usuario o clave incorrectos
        header('Location: formularioDeLogin.html?e=1');
        exit;
    }
    // Incrementar contador solo en la primera carga de la sesión
    $esNuevaSesion = !isset($_SESSION['id_usuario']);
    $nuevoContador = (int)$usuario['contador_sesion'];
    if ($esNuevaSesion) {
        $nuevoContador++;
    }
    // Guardar información en sesión
    $_SESSION['id_usuario']        = $usuario['id'];
    $_SESSION['login']             = $usuario['login'];
    $_SESSION['apellido_nombres']  = $usuario['apellido_nombres'];
    $_SESSION['contador_sesion']   = $nuevoContador;
    $_SESSION['timestamp_inicio']  = time();
    // Actualizar contador en BD si se incrementó
    if ($esNuevaSesion) {
        $upd = $pdo->prepare('UPDATE usuarios SET contador_sesion = :c WHERE id = :id');
        $upd->execute([':c' => $nuevoContador, ':id' => $usuario['id']]);
    }
    // Redirigir a la página de bienvenida
    header('Location: index.php');
    exit;
} catch (Throwable $e) {
    // En caso de error, retornar al login con código de error genérico
    header('Location: formularioDeLogin.html?e=1');
    exit;
}
?>
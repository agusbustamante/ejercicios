<?php
/*
 * destruirsesion.php — Terminación de sesión
 *
 * Este script destruye la sesión actual, elimina cualquier dato
 * asociado en $_SESSION y redirige al formulario de login. Es el
 * destino del enlace "Terminar sesión" que aparece en index.php.
 */

session_start();
// Vaciar todas las variables de sesión
$_SESSION = [];
// Eliminar la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
// Destruir finalmente la sesión
session_destroy();

// Redirigir al login
header('Location: formularioDeLogin.html');
exit;
?>
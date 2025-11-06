<?php
/*
 * destruirsesion.php
 *
 * Cierra la sesión actual del usuario y lo redirige al formulario de
 * ingreso. Se eliminan todos los datos de la sesión y se destruye
 * la cookie de sesión para evitar reutilización. Basado en
 * destruirsesion.php de 11sesiones.
 */

session_start();

// Borrar todas las variables de sesión
$_SESSION = [];
// Eliminar la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
// Destruir la sesión
session_destroy();
// Redirigir al formulario de login
header('Location: formularioDeLogin.html');
exit;
?>
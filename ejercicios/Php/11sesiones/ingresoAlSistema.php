<?php
/*
 * ingresoAlSistema.php — Autenticación de usuarios
 *
 * Recibe las credenciales de acceso desde el formulario de login,
 * consulta la tabla `usuarios` para verificar la existencia del
 * usuario y la coincidencia de la clave almacenada (hash SHA-256),
 * gestiona la creación y regeneración de la sesión PHP, y actualiza
 * el contador de sesiones. Si las credenciales son incorrectas
 * redirecciona al formulario de login con un parámetro de error.
 */

// Asegurar que las entradas estén definidas
$login = trim($_POST['login'] ?? '');
$clave = trim($_POST['clave'] ?? '');

// Si faltan datos, redirigir de vuelta al login con un error
if ($login === '' || $clave === '') {
    header('Location: formularioDeLogin.html?e=1');
    exit;
}

require __DIR__ . '/app_modulo1/datosConexionBase.php';

try {
    // Hashear la clave con SHA-256 para comparar con la almacenada
    $hashClave = hash('sha256', $clave);

    // Buscar el usuario en la tabla usuarios
    $sql = 'SELECT id, login, nombre, password, contador_sesiones FROM usuarios WHERE login = :login';
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':login', $login);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !hash_equals($usuario['password'], $hashClave)) {
        // Usuario no encontrado o clave incorrecta
        header('Location: formularioDeLogin.html?e=1');
        exit;
    }

    // Crear o regenerar la sesión
    session_start();
    // Regenerar identificador para evitar fijación de sesión
    session_regenerate_id(true);

    // Incrementar contador de sesiones solo en el momento del login
    $contadorActual = (int)($usuario['contador_sesiones'] ?? 0);
    $nuevoContador  = $contadorActual + 1;
    // Guardar en sesión para mostrarlo en index.php
    $_SESSION['login']    = $usuario['login'];
    $_SESSION['nombre']   = $usuario['nombre'] ?? '';
    $_SESSION['contador'] = $nuevoContador;

    // Actualizar contador en la base
    $sqlUpdate = 'UPDATE usuarios SET contador_sesiones = :c WHERE id = :id';
    $up = $dbh->prepare($sqlUpdate);
    $up->bindValue(':c',  $nuevoContador, PDO::PARAM_INT);
    $up->bindValue(':id', $usuario['id'], PDO::PARAM_INT);
    $up->execute();

    // Redirigir al índice de la aplicación
    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    // Registrar en log y redirigir a login con error genérico
    file_put_contents(
        __DIR__.'/app_modulo1/errores.log',
        date('Y-m-d H:i') . " LOGIN: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    header('Location: formularioDeLogin.html?e=1');
    exit;
}
?>
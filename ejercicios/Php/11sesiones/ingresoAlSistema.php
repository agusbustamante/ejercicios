<?php
session_start();

$login = trim($_POST['login'] ?? '');
$clave = trim($_POST['clave'] ?? '');
if ($login === '' || $clave === '') {
    header('Location: formularioDeLogin.html?e=1'); exit;
}

require __DIR__ . '/datosConexionBase.php';

try {
    // Compara la clave en la CONSULTA (SHA2 en MySQL)
    $sql = 'SELECT id_usuario, login, nombre_apellido, contador_sesiones
            FROM usuarios
            WHERE login = :login AND clave_sha256 = SHA2(:clave, 256)';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':login' => $login, ':clave' => $clave]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        header('Location: formularioDeLogin.html?e=1'); exit;
    }

    // Sesión OK
    session_regenerate_id(true);
    $nuevoContador = (int)$u['contador_sesiones'] + 1;

    $_SESSION['login']    = $u['login'];
    $_SESSION['nombre']   = $u['nombre_apellido'] ?? '';
    $_SESSION['contador'] = $nuevoContador;

    // Actualiza contador
    $up = $dbh->prepare('UPDATE usuarios SET contador_sesiones = :c WHERE id_usuario = :id');
    $up->execute([':c' => $nuevoContador, ':id' => $u['id_usuario']]);

    header('Location: index.php'); exit;

} catch (Throwable $e) {
    @file_put_contents(__DIR__.'/app_modulo1/errores.log',
        date('Y-m-d H:i') . " LOGIN: " . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
    header('Location: formularioDeLogin.html?e=1'); exit;
}

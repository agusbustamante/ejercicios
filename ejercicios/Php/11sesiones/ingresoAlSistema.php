<?php
session_start();

$login = trim($_POST['login'] ?? '');
$clave = trim($_POST['clave'] ?? '');

if ($login === '' || $clave === '') {
    header('Location: formularioDeLogin.html?e=1'); 
    exit;
}

require __DIR__ . '/datosConexionBase.php';

try {
    // Calcular el SHA256 de la contraseña
    $clave_sha256 = hash('sha256', $clave);
    
    // Compara la clave en la consulta
    $sql = 'SELECT id_usuario, login, nombre_apellido, contador_sesiones
            FROM usuarios
            WHERE login = :login AND clave_sha256 = :clave_sha256';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':login' => $login, ':clave_sha256' => $clave_sha256]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        file_put_contents(__DIR__.'/errores.log',
            date('Y-m-d H:i') . " LOGIN_FALLIDO: login=$login, hash enviado=$clave_sha256" . PHP_EOL,
            FILE_APPEND
        );
        header('Location: formularioDeLogin.html?e=1'); 
        exit;
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

    header('Location: index.php'); 
    exit;

} catch (Throwable $e) {
    file_put_contents(__DIR__.'/errores.log',
        date('Y-m-d H:i') . " LOGIN_ERROR: " . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
    header('Location: formularioDeLogin.html?e=1'); 
    exit;
}

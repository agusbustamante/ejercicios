<?php
/*
 * ingresoAlSistema.php (corregido)
 * Valida con SHA-256 y columnas reales:
 *  id_usuario, login, nombre_apellido, clave_sha256, contador_sesiones
 */

session_start();
require_once __DIR__ . '/datosConexionBase.php';

// Solo POST desde el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formularioDeLogin.html');
    exit;
}

$login = trim($_POST['login'] ?? '');
$clave = $_POST['clave'] ?? '';

if ($login === '' || $clave === '') {
    header('Location: formularioDeLogin.html?e=1'); // faltan datos
    exit;
}

try {
    $pdo = conectar();

    // ✅ Validación directa con SHA2 en SQL y nombres de columnas correctos
    $sql = "SELECT id_usuario, login, nombre_apellido, contador_sesiones
            FROM usuarios
            WHERE login = :login
              AND clave_sha256 = SHA2(:clave,256)
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':login' => $login, ':clave' => $clave]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usr) {
        // Usuario o clave incorrectos
        header('Location: formularioDeLogin.html?e=1');
        exit;
    }

    // ✅ Login OK: setear sesión
    $_SESSION['id_usuario']       = (int)$usr['id_usuario'];
    $_SESSION['login']            = $usr['login'];
    // guardo con el mismo nombre que usa el resto de la app
    $_SESSION['apellido_nombres'] = $usr['nombre_apellido'];
    $_SESSION['timestamp_inicio'] = time();

    // ✅ Incremento del contador solo una vez por sesión
    if (empty($_SESSION['contador_incrementado'])) {
        $upd = $pdo->prepare(
            "UPDATE usuarios
             SET contador_sesiones = contador_sesiones + 1
             WHERE id_usuario = :id"
        );
        $upd->execute([':id' => $usr['id_usuario']]);
        $_SESSION['contador_incrementado'] = true;
    }

    // Ir a la portada (igual que 11sesiones)
    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    // Podés loguear $e->getMessage() si querés
      header('Location: app_modulo1/index.html');
    exit;
}

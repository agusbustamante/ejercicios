<?php
session_start();

if (!isset($_SESSION['login'])) {
    header('Location: formularioDeLogin.html');
    exit;
}

$sessionId  = session_id();
$login      = $_SESSION['login'] ?? '';
$contador   = $_SESSION['contador'] ?? 0;

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso a la aplicación</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 0.5rem;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .actions {
            margin-top: 1rem;
        }
        .actions a {
            display: inline-block;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .actions a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ingreso a la aplicación</h1>
        <p>Ha iniciado sesión correctamente. A continuación se muestra
        información de su sesión:</p>
        <table>
            <tr><th>Identificativo de sesión</th><td><?php echo htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Login de usuario</th><td><?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Contador de sesión</th><td><?php echo (int)$contador; ?></td></tr>
        </table>
        <div class="actions">
            <a href="app_modulo1/index.html">Ingrese al módulo 1 de la app</a>
            <a href="destruirsesion.php">Terminar sesión</a>
        </div>
    </div>
</body>
</html>
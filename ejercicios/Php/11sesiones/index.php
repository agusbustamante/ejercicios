<?php

session_start();

// Si no existe variable de sesión, redirigir al formulario de login
if (!isset($_SESSION['login'])) {
    header('Location: formularioDeLogin.html');
    exit;
}

// Recuperar datos de sesión
$sessionId  = session_id();
$login      = $_SESSION['login'] ?? '';
$contador   = $_SESSION['contador'] ?? 0;

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso a la aplicación</title>
    <!-- Utilizamos la misma hoja de estilos que el ABM para que la tabla luzca igual -->
    <!-- Añadimos parámetro de versión para forzar recarga del CSS -->
    <link rel="stylesheet" href="app_modulo1/styles.css?v=1" />
</head>
<body>

    <!-- ENCABEZADO -->
    <header class="barra-superior">
        <div class="barra-inner">
            <h1 class="titulo">Ingreso a la aplicación</h1>
            <!-- Acciones de navegación -->
            <div class="acciones-header">
                <a href="app_modulo1/index.html" class="boton">Módulo 1</a>
                <a href="destruirsesion.php" class="boton">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="tabla-contenedor">
        <div class="tabla-wrapper" style="margin-top:16px;">
            <p style="padding: 0 16px;">Ha iniciado sesión correctamente. A continuación se muestra información de su sesión:</p>
            <table aria-label="Tabla de información de la sesión">
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Identificativo de sesión</td>
                        <td><?php echo htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>Login de usuario</td>
                        <td><?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>Contador de sesión</td>
                        <td><?php echo (int)$contador; ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td id="pie" colspan="2">Alumno: <strong>Bustamante Agustin</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>

    <!-- PIE DE PÁGINA -->
    <footer class="pie">Programación en ambiente de redes – 2025 · Alumno: Bustamante Agustin</footer>

</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Variables de Servidor</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<h2>Variables de servidor</h2>
<table>
    <tr><td>SERVER_ADDR</td><td><?php echo $_SERVER['SERVER_ADDR']; ?></td></tr>
    <tr><td>SERVER_PORT</td><td><?php echo $_SERVER['SERVER_PORT']; ?></td></tr>
    <tr><td>SERVER_NAME</td><td><?php echo $_SERVER['SERVER_NAME']; ?></td></tr>
    <tr><td>HTTP_HOST</td><td><?php echo $_SERVER['HTTP_HOST']; ?></td></tr>
    <tr><td>DOCUMENT_ROOT</td><td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td></tr>
</table>

<h2>Variables de cliente</h2>
<table>
    <tr><td>REMOTE_ADDR</td><td><?php echo $_SERVER['REMOTE_ADDR']; ?></td></tr>
    <tr><td>REMOTE_PORT</td><td><?php echo $_SERVER['REMOTE_PORT']; ?></td></tr>
</table>

<h2>Variables de Requerimiento</h2>
<table>
    <tr><td>SCRIPT_NAME</td><td><?php echo $_SERVER['SCRIPT_NAME']; ?></td></tr>
    <tr><td>REQUEST_METHOD</td><td><?php echo $_SERVER['REQUEST_METHOD']; ?></td></tr>
    <tr><td>REQUEST_URI</td><td><?php echo $_SERVER['REQUEST_URI']; ?></td></tr>
    <tr><td>QUERY_STRING</td><td><?php echo $_SERVER['QUERY_STRING']; ?></td></tr>
</table>

<h2>TODAS</h2>
<?php
// Variables que ya mostramos en las tablas anteriores
$variables_mostradas = [
    'SERVER_ADDR',
    'SERVER_PORT', 
    'SERVER_NAME',
    'HTTP_HOST',
    'DOCUMENT_ROOT',
    'REMOTE_ADDR',
    'REMOTE_PORT',
    'SCRIPT_NAME',
    'REQUEST_METHOD',
    'REQUEST_URI',
    'QUERY_STRING'
];

// Mostrar todas las variables restantes de $_SERVER
foreach ($_SERVER as $clave => $valor) {
    // Solo mostrar si no está en la lista de variables ya mostradas
    if (!in_array($clave, $variables_mostradas)) {
        echo $clave . " = " . $valor . "<br>";
    }
}
?>

</body>
</html>
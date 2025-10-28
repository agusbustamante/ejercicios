<!DOCTYPE html>
<html>
<head>
    <title>Fecha y Hora del Servidor</title>
</head>
<body>
    <h2>
        <?php
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        echo "Fecha y hora del servidor: " . date("d/m/Y H:i:s");
        ?>
    </h2>
</body>
</html>

<?php
if (isset($_POST['clave'])) {
    $clave = $_POST['clave'];

    // Encriptaciones
    $claveMd5 = md5($clave);
    $claveSha256 = hash('sha256', $clave);

    // Mostrar resultados
    echo "Clave: $clave<br>";
    echo "Clave encriptada en md5 (128 bits o 16 octetos o 16 pares hexadecimales):<br>$claveMd5<br><br>";
    echo "Clave: $clave<br>";
    echo "Clave encriptada en sha256 (256 bits o 32 octetos o 32 pares hexadecimales):<br>$claveSha256<br><br>";

    echo '<a href="index.php">Volver</a>';
} else {
    ?>
    <html>
    <head><meta charset="utf-8">
    <title>form to encrypt</title></head>
    <body>
        <form method="post" action="">
            Ingrese la clave a encriptar:
            <input type="text" name="clave">
            <input type="submit" value="Obtener encriptación">
        </form>
    </body>
    </html>
    <?php
}
?>

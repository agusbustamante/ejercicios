<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario PHP</title>
</head>
<body>

<?php
// Mostrar el formulario 
echo '<form action="respuesta.php" method="get">'; // Formulario que envía datos por GET a respuesta.php
echo 'Nombre y apellido:<br>';
echo '<input type="text" name="nombre"><br><br>';
echo 'Edad:<br>';
echo '<input type="text" name="edad"><br><br>';
echo '<input type="submit" value="Ingrese la informacion">';
echo '</form>'; // Cierre del formulario
?>

</body>
</html>
    
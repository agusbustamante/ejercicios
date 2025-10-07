<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejemplo include()</title>
</head>
<body>

<h3>Se utiliza la función include() que ubica código php definido en otro archivo asignaciones.php :</h3>
<p>Antes de insertar el include las variables declaradas en el mismo no existen</p>
<p> A pesar de ello el ciclo de ejecución continuará hasta el final</p>

<?php
// Intentar mostrar variables antes del include (produce warnings)
echo "<h4>Las variables son:</h4>";
echo $arreglo1["nombre"] . " " . $arreglo1["apellido"] . " " . $arreglo1["anioNacimiento"] . "<br>";
echo $arreglo2["nombre"] . " " . $arreglo2["apellido"] . " " . $arreglo2["anioNacimiento"] . "<br>";

// Mostrar tabla vacía
echo "<table border='1' cellpadding='5'>";
echo "<tr><td></td><td></td><td></td></tr>";
echo "<tr><td></td><td></td><td></td></tr>";
echo "</table>";

echo "<hr />";

//se ejecuta el include
echo "<h4>Se ejecuta la función include().</h4>";
include("asignaciones.php");

echo "<h4>Las 2 variables de tipo array asociativo en el archivo asociado son:</h4>";

// Mostrar contenido de los arreglos después del include
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>{$arreglo1['nombre']}</td><td>{$arreglo1['apellido']}</td><td>{$arreglo1['anioNacimiento']}</td></tr>";
echo "<tr><td>{$arreglo2['nombre']}</td><td>{$arreglo2['apellido']}</td><td>{$arreglo2['anioNacimiento']}</td></tr>";
echo "</table>";

echo "<p>La longitud del arreglo1 es : " . count($arreglo1) . "</p>";
echo "<p>La longitud del arreglo2 es : " . count($arreglo2) . "</p>";
?>

</body>
</html>

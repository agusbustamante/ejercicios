<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ejemplo Variables PHP</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<p>Texto fuera de php</p>

<?php
echo "<h4>Texto y/o HTML entregado por el procesador PHP usando la sentencia echo.</h4>";

$variableA = "valor1";
$variableB = 2;
$variableC = 3;
$variableD = $variableB + $variableC;
$variableE = true;
$variableF = false;
$aPalabra = ["Hola", "Hello"];

define("MICONSTANTE", "valorConstante");

echo "<div class='bloque'>";
echo "El valor de \$variableA es: $variableA<br>";
echo "El tipo de \$variableA es: " . gettype($variableA);
echo "</div>";

echo "<div class='bloque'>";
echo "El valor de \$variableB es: $variableB<br>";
echo "El tipo de \$variableB es: " . gettype($variableB);
echo "</div>";

echo "<div class='bloque'>";
echo "El valor de \$variableC es: $variableC<br>";
echo "El tipo de \$variableC es: " . gettype($variableC);
echo "</div>";

echo "<div class='comentario'>variableD es la suma de variableB y variableC</div>";
echo "<div class='comentario'>Si los tipos fueran diferentes, PHP devolvería error.</div>";

echo "<div class='bloque'>";
echo "El valor de \$variableD es: $variableD<br>";
echo "El tipo de \$variableD es: " . gettype($variableD);
echo "</div>";

echo "<div class='bloque'>";
echo "Variable tipo booleana (verdadero) \$variableE: $variableE<br>";
echo "El tipo de \$variableE es: " . gettype($variableE);
echo "</div>";

echo "<div class='bloque'>";
echo "Variable tipo booleana (falso) \$variableF: $variableF<br>";
echo "El tipo de \$variableF es: " . gettype($variableF);
echo "</div>";

echo "<div class='bloque'>";
echo "MICONSTANTE: " . MICONSTANTE . "<br>";
echo "El tipo de MICONSTANTE es: " . gettype(MICONSTANTE);
echo "</div>";

echo "<div class='bloque'>";
echo "El valor del primer elemento del array aPalabra es: " . $aPalabra[0] . "<br>";
echo "El valor del segundo elemento del array aPalabra es: " . $aPalabra[1] . "<br>";
echo "El tipo de aPalabra es: " . gettype($aPalabra) . "<br>";
echo array_push($aPalabra, "bonjour");
echo array_push($aPalabra, "Ciao");
echo "<br>Todos los elementos del array aPalabra son: ";
foreach ($aPalabra as $palabra) {
    echo  "</br>" . $palabra;
}
?>

</body>
</html>

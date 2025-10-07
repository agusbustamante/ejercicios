<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Variables tipo objeto en PHP</title>
</head>
<body>

<h2>Variables tipo objeto en PHP. Objeto renglón de pedido</h2>

<p><span style="color:blue;"><b>$objRenglonPedido</b></span></p>

<?php
// Punto 1: Creación del objeto
$objRenglonPedido = new stdClass();
$objRenglonPedido->codArt = "cp001";
$objRenglonPedido->descripcion = "jaguel 800 gr";
$objRenglonPedido->precioUnitario = 30;
$objRenglonPedido->cantidad = 2;

echo "Codigo de articulo: " . $objRenglonPedido->codArt . "<br>";
echo "Descripcion del articulo: " . $objRenglonPedido->descripcion . "<br>";
echo "Precio unitario: " . $objRenglonPedido->precioUnitario . "<br>";
echo "Cantidad: " . $objRenglonPedido->cantidad . "<br><br>";

echo "Tipo de <span style='color:blue;'><b>\$objRenglonPedido</b></span>: " . gettype($objRenglonPedido) . "<br><br>";

// Punto 2: Arreglo de objetos
echo "Definamos arreglo de pedidos:<br><br>";
echo "<span style='color:blue;'><b>\$renglonesPedido</b></span><br><br>";

$renglonesPedido = [];
array_push($renglonesPedido, $objRenglonPedido);

$objRenglonPedido2 = new stdClass();
$objRenglonPedido2->codArt = "cp002";
$objRenglonPedido2->descripcion = "atun 800 gr";
$objRenglonPedido2->precioUnitario = 24;
$objRenglonPedido2->cantidad = 3;

array_push($renglonesPedido, $objRenglonPedido2);

echo "Tipo de <span style='color:blue;'><b>\$renglonesPedido</b></span>: " . gettype($renglonesPedido) . "<br><br>";

echo "Tabula <span style='color:blue;'><b>\$renglonesPedido</b></span>. Recorrer el arreglo de renglones y tabularlos con html:<br><br>";

// Mostrar los objetos
foreach ($renglonesPedido as $r) {
    echo $r->codArt . " " . $r->descripcion . " " . $r->precioUnitario . " " . $r->cantidad . "<br>";
}

$cantidadRenglones = count($renglonesPedido);
echo "<br>Cantidad de renglones" . $cantidadRenglones . "<br><br>";

// Punto 3: Objeto contenedor
echo "Produccion de un objeto <span style='color:blue;'><b>\$objRenglonesPedido</b></span> con dos atributos array renglonesPedido y cantidadDeRenglones:<br><br>";

$objRenglonesPedido = new stdClass();
$objRenglonesPedido->renglonesPedido = $renglonesPedido;
$objRenglonesPedido->cantidadDeRenglones = $cantidadRenglones;

echo "Cantidad de renglones: " . $objRenglonesPedido->cantidadDeRenglones . "<br><br>";

// Punto 4: JSON
echo "Produccion de un JSON jsonRenglones:<br><br>";

$jsonRenglones = json_encode($objRenglonesPedido);
echo $jsonRenglones;
?>

</body>
</html>

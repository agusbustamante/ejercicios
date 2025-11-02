<?php
// test_conn.php — prueba rápida de conexión y saneamiento
require __DIR__.'/datosConexionBase.php';
header('Content-Type: text/plain; charset=utf-8');

echo "---- Test de conexión automática ----\n";
if (isset($_CFG)) {
    echo "Configuración (_CFG):\n";
    foreach ($_CFG as $k => $v) {
        echo "  $k: $v\n";
    }
} else {
    echo "No existe \\$_CFG\n";
}

if (isset($dbh) && $dbh instanceof PDO) {
    try {
        $r = $dbh->query('SELECT 1')->fetchColumn();
        echo "Conexión OK. SELECT 1 => $r\n";

        // Intentar contar filas (si la tabla existe)
        try {
            $count = $dbh->query('SELECT COUNT(*) FROM liquidacionesdesueldos')->fetchColumn();
            echo "Filas en tabla liquidacionesdesueldos: $count\n";
        } catch (Throwable $e) {
            echo "Nota: no se pudo contar la tabla o la tabla no existe: " . $e->getMessage() . "\n";
        }

    } catch (Throwable $e) {
        echo "Conexión establecida pero error en consulta: " . $e->getMessage() . "\n";
    }
} else {
    echo "No existe \$dbh o no es PDO. Revisa errores.log para detalles.\n";
}

echo "---- Fin Test ----\n";
?>

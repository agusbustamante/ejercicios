<?php
session_start();
if (!isset($_SESSION['login'])) {
    echo 'No autorizado';
    exit;
}

require __DIR__.'/../datosConexionBase.php';

echo "<h2>Diagnóstico de PDFs</h2>";

// 1. Verificar estructura de tabla
echo "<h3>1. Estructura de tabla:</h3>";
try {
    $stmt = $dbh->query("DESCRIBE liquidacionesdesueldos");
    $columnas = $stmt->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columnas as $col) {
        echo "<tr>";
        foreach ($col as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 2. Verificar registros con PDF
echo "<h3>2. Registros con PDF:</h3>";
try {
    $stmt = $dbh->query("SELECT LegajoEmpleado, ApellidoYNombres, LENGTH(pdf_liquidacion) as pdf_bytes FROM liquidacionesdesueldos ORDER BY LegajoEmpleado");
    $registros = $stmt->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>Legajo</th><th>Nombre</th><th>Bytes PDF</th></tr>";
    foreach ($registros as $reg) {
        $bytes = $reg['pdf_bytes'] ?? 0;
        $color = $bytes > 0 ? 'green' : 'red';
        echo "<tr style='color: $color'>";
        echo "<td>" . htmlspecialchars($reg['LegajoEmpleado']) . "</td>";
        echo "<td>" . htmlspecialchars($reg['ApellidoYNombres']) . "</td>";
        echo "<td>$bytes</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 3. Verificar $_FILES en subida
echo "<h3>3. Test de subida:</h3>";
echo "<form enctype='multipart/form-data' method='post'>";
echo "<input type='file' name='test_pdf' accept='.pdf'>";
echo "<input type='submit' value='Test Subida'>";
echo "</form>";

if (isset($_FILES['test_pdf'])) {
    echo "<h4>Resultado del test:</h4>";
    echo "<pre>";
    print_r($_FILES['test_pdf']);
    echo "</pre>";
    
    if (is_uploaded_file($_FILES['test_pdf']['tmp_name'])) {
        $size = filesize($_FILES['test_pdf']['tmp_name']);
        $content = file_get_contents($_FILES['test_pdf']['tmp_name']);
        echo "<p>Archivo válido. Tamaño: $size bytes</p>";
        echo "<p>Primeros 100 caracteres del contenido:</p>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 100)) . "</pre>";
        
        // Verificar que sea PDF
        if (strpos($content, '%PDF') === 0) {
            echo "<p style='color:green'>✅ Es un PDF válido</p>";
        } else {
            echo "<p style='color:red'>❌ No es un PDF válido</p>";
        }
    }
}
?>
<?php
session_start();
if (!isset($_SESSION['login'])) {
    echo 'No autorizado';
    exit;
}

require __DIR__.'/../datosConexionBase.php';

echo "<h2>Diagnóstico de Subida de PDFs</h2>";
echo "<style>
.error { color: red; font-weight: bold; }
.success { color: green; font-weight: bold; }
.info { color: blue; }
.section { border: 1px solid #ccc; margin: 10px 0; padding: 10px; }
</style>";

// Función para mostrar el estado
function mostrarEstado($condicion, $textoExito, $textoError) {
    if ($condicion) {
        echo "<p class='success'>✅ $textoExito</p>";
    } else {
        echo "<p class='error'>❌ $textoError</p>";
    }
    return $condicion;
}

echo "<div class='section'>";
echo "<h3>1. Configuración del Servidor PHP</h3>";

// Verificar configuración de subida
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxExecutionTime = ini_get('max_execution_time');
$fileUploads = ini_get('file_uploads');

echo "<p class='info'>upload_max_filesize: $uploadMaxFilesize</p>";
echo "<p class='info'>post_max_size: $postMaxSize</p>";
echo "<p class='info'>max_execution_time: $maxExecutionTime segundos</p>";
mostrarEstado($fileUploads == '1', "File uploads habilitado", "File uploads DESHABILITADO");

echo "</div>";

echo "<div class='section'>";
echo "<h3>2. Test de Subida de PDF</h3>";
echo "<form enctype='multipart/form-data' method='post' action=''>";
echo "<p>Selecciona un archivo PDF para probar:</p>";
echo "<input type='file' name='test_pdf' accept='.pdf' required>";
echo "<br><br>";
echo "<input type='text' name='legajo_test' placeholder='Legajo de prueba (ej: E001)' required>";
echo "<br><br>";
echo "<input type='submit' name='test_upload' value='Probar Subida'>";
echo "</form>";
echo "</div>";

if (isset($_POST['test_upload'])) {
    echo "<div class='section'>";
    echo "<h3>3. Resultado del Test</h3>";
    
    $legajoTest = trim($_POST['legajo_test'] ?? '');
    echo "<p class='info'>Legajo de prueba: $legajoTest</p>";
    
    // Verificar $_FILES
    echo "<h4>Estado de \$_FILES:</h4>";
    if (!isset($_FILES['test_pdf'])) {
        echo "<p class='error'>❌ \$_FILES['test_pdf'] no existe</p>";
    } else {
        $archivo = $_FILES['test_pdf'];
        echo "<pre>";
        foreach ($archivo as $key => $value) {
            echo "$key: " . (is_array($value) ? print_r($value, true) : $value) . "\n";
        }
        echo "</pre>";
        
        // Verificar errores de subida
        $error = $archivo['error'];
        $errores = [
            UPLOAD_ERR_OK => 'Sin errores',
            UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir archivo',
            UPLOAD_ERR_EXTENSION => 'Extensión bloqueó la subida'
        ];
        
        $mensajeError = $errores[$error] ?? "Error desconocido ($error)";
        mostrarEstado($error === UPLOAD_ERR_OK, "Archivo subido correctamente", "Error de subida: $mensajeError");
        
        if ($error === UPLOAD_ERR_OK) {
            $tmpName = $archivo['tmp_name'];
            $size = $archivo['size'];
            $type = $archivo['type'];
            
            echo "<h4>Detalles del archivo:</h4>";
            echo "<p class='info'>Tamaño: $size bytes</p>";
            echo "<p class='info'>Tipo MIME: $type</p>";
            
            // Verificar que sea PDF
            mostrarEstado($type === 'application/pdf', "Tipo MIME correcto", "Tipo MIME incorrecto: $type");
            
            // Verificar archivo temporal
            mostrarEstado(is_uploaded_file($tmpName), "Archivo temporal válido", "Archivo temporal inválido");
            
            if (is_uploaded_file($tmpName)) {
                // Leer contenido
                $contenido = file_get_contents($tmpName);
                $realSize = strlen($contenido);
                
                echo "<p class='info'>Tamaño real del contenido: $realSize bytes</p>";
                mostrarEstado($contenido !== false, "Contenido leído correctamente", "Error al leer contenido");
                
                if ($contenido) {
                    // Verificar que sea PDF real
                    $esPDF = substr($contenido, 0, 4) === '%PDF';
                    mostrarEstado($esPDF, "Es un PDF válido (inicia con %PDF)", "NO es un PDF válido");
                    
                    if ($esPDF && !empty($legajoTest)) {
                        echo "<h4>Intentando guardar en base de datos:</h4>";
                        
                        try {
                            // Verificar si el legajo existe
                            $stmt = $dbh->prepare("SELECT LegajoEmpleado FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
                            $stmt->bindValue(':legajo', $legajoTest);
                            $stmt->execute();
                            $existe = $stmt->fetch();
                            
                            if ($existe) {
                                echo "<p class='success'>✅ Legajo existe en la base de datos</p>";
                                
                                // Intentar guardar PDF
                                $sql = "UPDATE liquidacionesdesueldos SET pdf_liquidacion = :pdf WHERE LegajoEmpleado = :legajo";
                                $stmt = $dbh->prepare($sql);
                                $stmt->bindParam(':pdf', $contenido, PDO::PARAM_LOB);
                                $stmt->bindValue(':legajo', $legajoTest);
                                $resultado = $stmt->execute();
                                
                                if ($resultado) {
                                    echo "<p class='success'>✅ PDF guardado en base de datos correctamente</p>";
                                    
                                    // Verificar que se guardó
                                    $stmt = $dbh->prepare("SELECT LENGTH(pdf_liquidacion) as bytes FROM liquidacionesdesueldos WHERE LegajoEmpleado = :legajo");
                                    $stmt->bindValue(':legajo', $legajoTest);
                                    $stmt->execute();
                                    $verificacion = $stmt->fetch();
                                    
                                    if ($verificacion && $verificacion['bytes'] > 0) {
                                        echo "<p class='success'>✅ Verificación: PDF tiene {$verificacion['bytes']} bytes en la BD</p>";
                                    } else {
                                        echo "<p class='error'>❌ Verificación: PDF no se guardó o está vacío</p>";
                                    }
                                } else {
                                    echo "<p class='error'>❌ Error al ejecutar UPDATE en base de datos</p>";
                                    $errorInfo = $stmt->errorInfo();
                                    echo "<pre>Error SQL: " . print_r($errorInfo, true) . "</pre>";
                                }
                            } else {
                                echo "<p class='error'>❌ El legajo '$legajoTest' no existe en la base de datos</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p class='error'>❌ Error de base de datos: " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
        }
    }
    echo "</div>";
}

echo "<div class='section'>";
echo "<h3>4. Verificar Alta.php y Modi.php</h3>";

$archivosVerificar = ['alta.php', 'modi.php'];
foreach ($archivosVerificar as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    if (file_exists($ruta)) {
        $contenido = file_get_contents($ruta);
        echo "<h4>$archivo:</h4>";
        
        // Verificar elementos clave
        $tieneMultipart = strpos($contenido, 'multipart/form-data') !== false;
        $tieneFiles = strpos($contenido, '$_FILES') !== false;
        $tieneIsUploaded = strpos($contenido, 'is_uploaded_file') !== false;
        $tieneParamLob = strpos($contenido, 'PDO::PARAM_LOB') !== false;
        
        mostrarEstado($tieneFiles, "Procesa \$_FILES", "NO procesa \$_FILES");
        mostrarEstado($tieneIsUploaded, "Usa is_uploaded_file()", "NO usa is_uploaded_file()");
        mostrarEstado($tieneParamLob, "Usa PDO::PARAM_LOB", "NO usa PDO::PARAM_LOB");
    } else {
        echo "<p class='error'>❌ $archivo no existe</p>";
    }
}

echo "</div>";
?>
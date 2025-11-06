<?php
/*
 * alta.php
 *
 * Inserta un nuevo registro en la tabla liquidaciones. El archivo PDF se
 * gestiona en una segunda etapa mediante cargarPdfs.php, por lo tanto
 * aquí se ignora cualquier archivo adjunto. Se realizan validaciones de
 * campos obligatorios y de unicidad de la clave primaria.
 *
 * Basado en el alta.php de 11sesiones y en los requerimientos
 * adicionales especificados en el enunciado.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

try {
    $legajo = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha  = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes    = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo = trim($_POST['SueldoBasico'] ?? '');
    // Tomar concepto de cualquiera de los campos permitidos
    $concepto = trim($_POST['CodConceptoNoRem'] ?? ($_POST['concepto_no_remunerativo_1'] ?? ''));
    $montoNR1 = trim($_POST['Monto_no_remunerativo_1'] ?? '');

    // Validaciones de servidor
    if ($legajo === '' || $apynom === '' || $fecha === '' || $mes === '' || $sueldo === '' || $concepto === '' || $montoNR1 === '') {
        echo json_encode(['ok' => false, 'estado' => 'Faltan datos obligatorios']);
        exit;
    }
    $pdo = conectar();
    // Verificar unicidad de la clave primaria
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM liquidaciones WHERE LegajoEmpleado = :legajo');
    $stmt->execute([':legajo' => $legajo]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'estado' => 'Legajo duplicado']);
        exit;
    }
    // Insertar datos simples
    $stmt = $pdo->prepare('INSERT INTO liquidaciones (LegajoEmpleado, ApellidoYNombres, Fecha_liquidacion, MesDeLiquidacion, SueldoBasico, concepto_no_remunerativo_1, Monto_no_remunerativo_1) VALUES (:legajo, :apynom, :fecha, :mes, :sueldo, :concepto, :monto)');
    $stmt->execute([
        ':legajo'   => $legajo,
        ':apynom'   => $apynom,
        ':fecha'    => $fecha,
        ':mes'      => $mes,
        ':sueldo'   => $sueldo,
        ':concepto' => $concepto,
        ':monto'    => $montoNR1
    ]);
    echo json_encode(['ok' => true, 'estado' => 'Alta exitosa']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'estado' => 'Error: ' . $e->getMessage()]);
}
?>
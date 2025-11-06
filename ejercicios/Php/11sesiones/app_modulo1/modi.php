<?php
/*
 * modi.php
 *
 * Modifica los datos simples de una liquidación. El PDF asociado no se
 * actualiza en este paso, sino mediante cargarPdfs.php en una segunda
 * etapa. Se controla que un cambio de clave primaria no viole la
 * unicidad. Adaptado del modi.php de 11sesiones y de nuestra
 * implementación anterior.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

try {
    $legajoOriginal = trim($_POST['LegajoEmpleadoOriginal'] ?? '');
    $legajo         = trim($_POST['LegajoEmpleado'] ?? '');
    $apynom         = trim($_POST['ApellidoYNombres'] ?? '');
    $fecha          = trim($_POST['Fecha_liquidacion'] ?? '');
    $mes            = trim($_POST['MesDeLiquidacion'] ?? '');
    $sueldo         = trim($_POST['SueldoBasico'] ?? '');
    $concepto       = trim($_POST['CodConceptoNoRem'] ?? ($_POST['concepto_no_remunerativo_1'] ?? ''));
    $montoNR1       = trim($_POST['Monto_no_remunerativo_1'] ?? '');
    if ($legajoOriginal === '' || $legajo === '' || $apynom === '' || $fecha === '' || $mes === '' || $sueldo === '' || $concepto === '' || $montoNR1 === '') {
        echo json_encode(['ok' => false, 'estado' => 'Faltan datos obligatorios']);
        exit;
    }
    $pdo = conectar();
    // Verificar que no se genere un duplicado si se cambia la PK
    if ($legajo !== $legajoOriginal) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM liquidaciones WHERE LegajoEmpleado = :legajo');
        $st->execute([':legajo' => $legajo]);
        if ($st->fetchColumn() > 0) {
            echo json_encode(['ok' => false, 'estado' => 'Legajo duplicado']);
            exit;
        }
    }
    $stmt = $pdo->prepare('UPDATE liquidaciones SET LegajoEmpleado = :legajo, ApellidoYNombres = :apynom, Fecha_liquidacion = :fecha, MesDeLiquidacion = :mes, SueldoBasico = :sueldo, concepto_no_remunerativo_1 = :concepto, Monto_no_remunerativo_1 = :monto WHERE LegajoEmpleado = :orig');
    $stmt->execute([
        ':legajo'   => $legajo,
        ':apynom'   => $apynom,
        ':fecha'    => $fecha,
        ':mes'      => $mes,
        ':sueldo'   => $sueldo,
        ':concepto' => $concepto,
        ':monto'    => $montoNR1,
        ':orig'     => $legajoOriginal
    ]);
    echo json_encode(['ok' => true, 'estado' => 'Modificación exitosa']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'estado' => 'Error: ' . $e->getMessage()]);
}
?>
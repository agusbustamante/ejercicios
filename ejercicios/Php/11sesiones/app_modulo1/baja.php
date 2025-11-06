<?php
/*
 * baja.php
 *
 * Elimina un registro de la tabla liquidaciones. Esta implementación
 * realiza una eliminación física (DELETE). Si se desea una baja
 * lógica, se podría añadir un campo de estado y actualizarlo en lugar
 * de borrar la fila. Basado en baja.php de 11sesiones.
 */

session_start();
require_once __DIR__ . '/../datosConexionBase.php';
checkSessionApp();
header('Content-Type: application/json; charset=utf-8');

try {
    $legajo = trim($_POST['LegajoEmpleado'] ?? '');
    if ($legajo === '') {
        echo json_encode(['ok' => false, 'estado' => 'Falta legajo']);
        exit;
    }
    $pdo = conectar();
    $stmt = $pdo->prepare('DELETE FROM liquidaciones WHERE LegajoEmpleado = :legajo');
    $stmt->execute([':legajo' => $legajo]);
    $msg = ($stmt->rowCount() > 0) ? 'Baja exitosa' : 'Registro no encontrado';
    echo json_encode(['ok' => true, 'estado' => $msg]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'estado' => 'Error: ' . $e->getMessage()]);
}
?>
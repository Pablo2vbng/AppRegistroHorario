<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = $_POST['fecha'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo']; // nacional, comunidad, local
    $descuenta = isset($_POST['descuenta']) ? 1 : 0;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO festivos (fecha, nombre, tipo, descuenta_vacaciones) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fecha, $nombre, $tipo, $descuenta]);

        // Si es cierre de empresa, restamos 1 día a todos los empleados
        if ($descuenta == 1) {
            $pdo->query("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles - 1 WHERE rol = 'empleado'");
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}
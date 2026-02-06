<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Solo Carmen (Admin) puede hacer ajustes manuales
if ($_SESSION['rol'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $tipo = $_POST['tipo'];
    $fecha_hora = $_POST['fecha_hora'];
    $notas = $_POST['notas'];
    $admin_id = $_SESSION['usuario_id'];

    try {
        $sql = "INSERT INTO fichajes (usuario_id, tipo, fecha_hora, notas, modificado_por, ip_registro) 
                VALUES (?, ?, ?, ?, ?, 'MANUAL-ADMIN')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $tipo, $fecha_hora, $notas, $admin_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
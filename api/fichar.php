<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo']; // entrada, pausa, reanudar, salida
    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $stmt = $pdo->prepare("INSERT INTO fichajes (usuario_id, tipo, ip_registro) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $tipo, $ip]);
        
        echo json_encode(['success' => true, 'message' => 'Registro de ' . $tipo . ' guardado con éxito']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en DB: ' . $e->getMessage()]);
    }
}
<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, ip_registro) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $tipo, $lat, $lng, $ip]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
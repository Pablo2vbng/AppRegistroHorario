<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'];
    $inicio = $_POST['fecha_inicio'];
    $fin = $_POST['fecha_fin'] ?? $inicio;
    $permuta_trabajo = $_POST['fecha_permuta_trabajo'] ?? null;
    $motivo = $_POST['motivo'];

    try {
        $sql = "INSERT INTO ausencias (usuario_id, tipo, fecha_inicio, fecha_fin, fecha_permuta_trabajo, motivo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $tipo, $inicio, $fin, $permuta_trabajo, $motivo]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
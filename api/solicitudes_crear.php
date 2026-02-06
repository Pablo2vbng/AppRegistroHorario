<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'];
    $inicio = $_POST['fecha_inicio'];
    $fin = $_POST['fecha_fin'];
    $ruta_archivo = null;

    // Gestión de subida de justificante médico
    if (isset($_FILES['justificante']) && $_FILES['justificante']['error'] == 0) {
        $uploadDir = '../uploads/justificantes/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['justificante']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['justificante']['tmp_name'], $targetFile)) {
            $ruta_archivo = 'uploads/justificantes/' . $fileName;
        }
    }

    try {
        $sql = "INSERT INTO ausencias (usuario_id, tipo, fecha_inicio, fecha_fin, archivo_justificante) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $tipo, $inicio, $fin, $ruta_archivo]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
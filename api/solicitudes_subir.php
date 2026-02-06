<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $file = $_FILES['archivo'];
    
    // Crear carpeta si no existe
    $targetDir = "../uploads/docs/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . "_" . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $dbPath = "uploads/docs/" . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO documentos (usuario_id, nombre_archivo, ruta) VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id, basename($file["name"]), $dbPath]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al mover el archivo']);
    }
}
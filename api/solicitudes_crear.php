<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'];
    $inicio = $_POST['fecha_inicio'];
    
    // CORRECCIÓN DE FECHAS
    $es_por_horas = isset($_POST['es_por_horas']) ? 1 : 0;
    
    if ($es_por_horas) {
        $fin = $inicio; // Si es por horas, fin es el mismo día
    } else {
        $fin = (!empty($_POST['fecha_fin'])) ? $_POST['fecha_fin'] : $inicio;
    }

    $permuta_trabajo = $_POST['fecha_permuta_trabajo'] ?? null;
    $horas = ($es_por_horas) ? ($_POST['horas_solicitadas'] ?? null) : null;
    $motivo = $_POST['motivo'] ?? ''; // Campo recuperado
    $ruta_archivo = null;

    // LÓGICA DE SUBIDA DE PARTE DE BAJA
    if (isset($_FILES['justificante']) && $_FILES['justificante']['error'] == 0) {
        $uploadDir = '../uploads/justificantes/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $extension = pathinfo($_FILES['justificante']['name'], PATHINFO_EXTENSION);
        $fileName = "BAJA_" . $usuario_id . "_" . time() . "." . $extension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['justificante']['tmp_name'], $targetFile)) {
            $ruta_archivo = 'uploads/justificantes/' . $fileName;
        }
    }

    try {
        $sql = "INSERT INTO ausencias (usuario_id, tipo, fecha_inicio, fecha_fin, fecha_permuta_trabajo, es_por_horas, horas_solicitadas, motivo, archivo_justificante) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $tipo, $inicio, $fin, $permuta_trabajo, $es_por_horas, $horas, $motivo, $ruta_archivo]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
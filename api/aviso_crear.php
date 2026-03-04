<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Por seguridad, comprobamos que el usuario esté logueado y sea admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado o sesión caducada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos el mensaje escrito por el admin
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (empty($mensaje)) {
        echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        exit();
    }

    try {
        // Insertamos el mensaje en la base de datos
        $stmt = $pdo->prepare("INSERT INTO avisos_generales (mensaje) VALUES (?)");
        $stmt->execute([htmlspecialchars($mensaje)]); // Sanitizamos por seguridad
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error al crear aviso general: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
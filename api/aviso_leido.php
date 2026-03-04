<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión caducada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aviso_id = filter_input(INPUT_POST, 'aviso_id', FILTER_VALIDATE_INT);
    $usuario_id = $_SESSION['usuario_id'];
    
    if (!$aviso_id) {
        echo json_encode(['success' => false, 'message' => 'ID de aviso inválido']);
        exit();
    }

    try {
        // Registramos que este empleado ya ha leído este aviso concreto
        $stmt = $pdo->prepare("INSERT INTO avisos_leidos (aviso_id, usuario_id) VALUES (?, ?)");
        $stmt->execute([$aviso_id, $usuario_id]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Si hay un error (por ejemplo, si por algún fallo intenta insertarlo dos veces y choca), no bloqueamos la app
        error_log("Error al marcar aviso como leído (Usuario: $usuario_id): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
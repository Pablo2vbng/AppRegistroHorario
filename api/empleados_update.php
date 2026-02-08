<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $horario = $_POST['horario'];
    $vac_totales = $_POST['vac_totales'];
    $vac_disponibles = $_POST['vac_disponibles'];
    $password = $_POST['password'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. Actualizar datos base
        $sql = "UPDATE usuarios SET nombre = ?, email = ?, horario = ?, dias_vacaciones_totales = ?, dias_vacaciones_disponibles = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $horario, $vac_totales, $vac_disponibles, $id]);

        // 2. Si hay password nueva, hashearla y actualizar
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtP = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmtP->execute([$hashed, $id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
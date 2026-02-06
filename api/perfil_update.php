<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['usuario_id'];
    $nombre = $_POST['nombre'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $hashed, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
        }
        
        $_SESSION['nombre'] = $nombre; // Actualizamos nombre en sesión
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
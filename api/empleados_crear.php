<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] == 'empleado') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, fecha_alta) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->execute([$nombre, $email, $pass, $rol]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
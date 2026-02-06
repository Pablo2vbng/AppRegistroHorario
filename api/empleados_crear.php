<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $horario = $_POST['horario'];

    try {
        // 1. Contar cuántos cierres de empresa hay este año
        $stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
        $cierres = $stmtC->fetchColumn();
        
        $totalBase = 22;
        $disponibles = $totalBase - $cierres;

        // 2. Insertar usuario con los días ya calculados
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, horario, dias_vacaciones_totales, dias_vacaciones_disponibles, fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$nombre, $email, $pass, $rol, $horario, $totalBase, $disponibles]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
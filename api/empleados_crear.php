<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    // Contraseña por defecto según tu instrucción: Nombre1234++
    $passText = $nombre . "1234++";
    $passHash = password_hash($passText, PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $horario = $_POST['horario'] ?? 'Flexible';

    try {
        // 1. Contar cierres de empresa actuales en Benigànim
        $stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
        $cierres = $stmtC->fetchColumn();
        
        $totalBase = 22;
        $disponibles = $totalBase - $cierres;

        // 2. Insertar usuario con saldo calculado
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, horario, dias_vacaciones_totales, dias_vacaciones_disponibles, fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$nombre, $email, $passHash, $rol, $horario, $totalBase, $disponibles]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
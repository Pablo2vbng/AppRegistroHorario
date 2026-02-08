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
    $horas_jornada = $_POST['horas_jornada'];
    
    // Convertimos los checkboxes de días en una cadena "1,2,3,4,5"
    $dias_laborables = isset($_POST['dias']) ? implode(',', $_POST['dias']) : '1,2,3,4,5';
    $password = $_POST['password'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. Actualizar datos maestros (Incluyendo horas y días laborables)
        $sql = "UPDATE usuarios SET 
                nombre = ?, 
                email = ?, 
                horario = ?, 
                dias_vacaciones_totales = ?, 
                dias_vacaciones_disponibles = ?, 
                horas_jornada = ?, 
                dias_laborables = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $horario, $vac_totales, $vac_disponibles, $horas_jornada, $dias_laborables, $id]);

        // 2. Si se ha escrito una contraseña nueva, la hasheamos y guardamos
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
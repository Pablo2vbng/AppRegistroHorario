<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $pdo->beginTransaction();

        // Miramos si descontaba vacaciones para devolver el día antes de borrar
        $stmtS = $pdo->prepare("SELECT descuenta_vacaciones FROM festivos WHERE id = ?");
        $stmtS->execute([$id]);
        if($stmtS->fetchColumn() == 1) {
            $pdo->query("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles + 1 WHERE rol = 'empleado'");
        }

        $stmt = $pdo->prepare("DELETE FROM festivos WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
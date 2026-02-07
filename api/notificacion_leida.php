<?php
require_once '../config/config.php';
if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE ausencias SET notificacion_vista = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_POST['id'], $_SESSION['usuario_id']]);
}
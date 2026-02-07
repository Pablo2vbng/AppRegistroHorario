<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['usuario_id'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $cp = $_POST['codigo_postal'];
    $password = $_POST['password'];
    
    $foto_path = null;

    try {
        $pdo->beginTransaction();

        // 1. GESTIÓN DE LA FOTO DE PERFIL
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $uploadDir = '../uploads/perfiles/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fileName = "AVATAR_" . $id . "_" . time() . "." . $ext;
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                $foto_path = 'uploads/perfiles/' . $fileName;
                // Actualizar foto en DB
                $stmtF = $pdo->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?");
                $stmtF->execute([$foto_path, $id]);
            }
        }

        // 2. ACTUALIZACIÓN DE DATOS BÁSICOS
        $sql = "UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ?, ciudad = ?, codigo_postal = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $telefono, $direccion, $ciudad, $cp, $id]);

        // 3. ACTUALIZACIÓN DE CONTRASEÑA (Solo si se rellena)
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtP = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmtP->execute([$hashed, $id]);
        }

        $_SESSION['nombre'] = $nombre; // Actualizar nombre en la sesión actual
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
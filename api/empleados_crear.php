<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Seguridad: Solo administradores
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpiamos los datos de entrada
    $nombre  = trim($_POST['nombre']);
    $email   = trim($_POST['email']);
    $rol     = $_POST['rol'] ?? 'user';
    $horario = $_POST['horario'] ?? 'Flexible';

    // Validaciones básicas
    if (empty($nombre) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y Email son obligatorios']);
        exit();
    }

    // Contraseña por defecto según tu instrucción: Nombre1234++
    // Usamos el nombre sin espacios para la contraseña por defecto
    $passText = str_replace(' ', '', $nombre) . "1234++";
    $passHash = password_hash($passText, PASSWORD_DEFAULT);

    try {
        // 1. Verificar si el email ya existe para evitar errores PDO
        $checkEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado']);
            exit();
        }

        // 2. Contar cierres de empresa actuales que descuentan vacaciones
        $stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
        $cierres = $stmtC->fetchColumn();
        
        $totalBase = 22;
        $disponibles = $totalBase - $cierres;

        // 3. Insertar usuario con saldo calculado
        $sql = "INSERT INTO usuarios (
                    nombre, 
                    email, 
                    password, 
                    rol, 
                    horario, 
                    dias_vacaciones_totales, 
                    dias_vacaciones_disponibles, 
                    fecha_alta
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre, 
            $email, 
            $passHash, 
            $rol, 
            $horario, 
            $totalBase, 
            $disponibles
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Empleado creado correctamente',
            'temp_pass' => $passText // Opcional: para que el admin sepa qué clave decirle
        ]);

    } catch (PDOException $e) {
        error_log("Error al crear empleado: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error interno al guardar en la base de datos'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
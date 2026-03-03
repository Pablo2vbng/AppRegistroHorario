<?php
require_once 'config/config.php';

// --- PROCESO DE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']); 
    $pass = trim($_POST['password']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]); 
        $user = $stmt->fetch();
        
        $loginOk = false;
        
        if ($user) {
            // 1. Verificación estándar por Hash
            if (password_verify($pass, $user['password'])) {
                $loginOk = true;
            } 
            // 2. Verificación de respaldo (Tu lógica de sufijos)
            else {
                $suffix = ($user['rol'] == 'admin') ? '1234+++' : '1234++';
                // Usamos str_replace para quitar espacios si los hubiera en el nombre para la clave legacy
                $nombreSinEspacios = str_replace(' ', '', $user['nombre']);
                if ($pass === $nombreSinEspacios . $suffix || $pass === $user['nombre'] . $suffix) {
                    $loginOk = true;
                }
            }
        }

        if ($loginOk) {
            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['usuario_id'] = $user['id']; 
            $_SESSION['nombre'] = $user['nombre']; 
            $_SESSION['rol'] = $user['rol'];
            
            header("Location: index.php"); 
            exit();
        } else {
            // Si el login falla, redirigimos con un parámetro de error
            header("Location: index.php?error=auth");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error de Login: " . $e->getMessage());
        die("Error temporal en el sistema. Intente más tarde.");
    }
}

// --- CONTROL DE ACCESO ---
if (!isset($_SESSION['usuario_id'])) { 
    // Si no hay sesión, cargamos la vista de login (pasando el error si existe)
    include 'views/login.php'; 
    exit(); 
}

// --- ENRUTADOR (ROUTING) ---
$p = $_GET['p'] ?? 'dashboard';

// Prevenimos que carguen vistas de admin si no lo son
$soloAdmin = [
    'empleados', 
    'empleado_detalle', 
    'gestion_ausencias', 
    'gestion_festivos', 
    'informe_legal', 
    'informe_global'
];

if (in_array($p, $soloAdmin) && $_SESSION['rol'] !== 'admin') {
    $p = 'dashboard';
}

include 'views/layout_header.php';

switch ($p) {
    case 'dashboard': 
        include 'views/dashboard.php'; 
        break;
    case 'calendario_anual': 
        include 'views/calendario_anual.php'; 
        break;
    case 'solicitudes': 
        include 'views/solicitudes.php'; 
        break;
    case 'perfil': 
        include 'views/perfil.php'; 
        break;
    case 'documentos': 
        include 'views/documentos.php'; 
        break;
    case 'informe_legal': 
        include 'views/rrhh/informe_legal.php'; 
        break; 
    case 'informe_global': 
        include 'views/rrhh/informe_global.php'; 
        break;
    case 'empleados': 
        include 'views/rrhh/empleados.php'; 
        break;
    case 'empleado_detalle': 
        include 'views/rrhh/empleado_detalle.php'; 
        break;
    case 'gestion_ausencias': 
        include 'views/rrhh/ausencias.php'; 
        break;
    case 'gestion_festivos': 
        include 'views/rrhh/festivos.php'; 
        break;
    case 'logout':
        session_destroy();
        header("Location: index.php");
        exit();
    default: 
        include 'views/dashboard.php'; 
        break;
}

include 'views/layout_footer.php';
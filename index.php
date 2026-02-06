<?php
/**
 * ARCHIVO PRINCIPAL - ENRUTADOR CVTools
 */
require_once 'config/config.php';

// 1. LÓGICA DE LOGIN (Procesar el formulario de entrada)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verificamos si el usuario existe y la contraseña es correcta
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        
        header("Location: index.php?p=dashboard");
        exit();
    } else {
        $error_login = "Credenciales incorrectas. Inténtalo de nuevo.";
    }
}

// 2. CONTROL DE ACCESO (Si no hay sesión, siempre mostramos el Login)
if (!isset($_SESSION['usuario_id'])) {
    include 'views/login.php';
    exit();
}

// 3. ENRUTADOR (Decidir qué vista cargar según el parámetro ?p=)
$pagina = isset($_GET['p']) ? $_GET['p'] : 'dashboard';

switch ($pagina) {
    
    // Vista principal de fichaje y estadísticas
    case 'dashboard':
        include 'views/dashboard.php';
        break;

    // Vista de RRHH: Gestión de plantilla (Solo Admin)
    case 'empleados':
        if ($_SESSION['rol'] == 'admin') {
            include 'views/rrhh/empleados.php';
        } else {
            header("Location: index.php?p=dashboard");
        }
        break;

    // Vista de Informes: Resumen mensual de horas
    case 'jornada':
        include 'views/jornada.php';
        break;

    // Vista de Solicitudes: Vacaciones y Bajas médicas
    case 'solicitudes':
        include 'views/solicitudes.php';
        break;
        
        case 'gestion_ausencias':
            if ($_SESSION['rol'] == 'admin') {
                include 'views/rrhh/ausencias.php';
            }
            break;
    // Redirección por defecto si la página no existe
    default:
        include 'views/dashboard.php';
        break;
}
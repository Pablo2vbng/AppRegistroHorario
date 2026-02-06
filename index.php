<?php
/**
 * ARCHIVO PRINCIPAL - ENRUTADOR CVTools
 */
require_once 'config/config.php';

// 1. LÓGICA DE LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php?p=dashboard");
        exit();
    } else {
        $error_login = "Credenciales incorrectas.";
    }
}

// 2. CONTROL DE ACCESO
if (!isset($_SESSION['usuario_id'])) {
    include 'views/login.php';
    exit();
}

// 3. ENRUTADOR RESPONSIVO
$p = isset($_GET['p']) ? $_GET['p'] : 'dashboard';

include 'views/layout_header.php';

switch ($p) {
    case 'dashboard':
        include 'views/dashboard.php';
        break;
    case 'empleados':
        if ($_SESSION['rol'] == 'admin') include 'views/rrhh/empleados.php';
        break;
    case 'informes_equipo':
        if ($_SESSION['rol'] == 'admin') include 'views/rrhh/informes_equipo.php';
        break;
    case 'gestion_ausencias':
        if ($_SESSION['rol'] == 'admin') include 'views/rrhh/ausencias.php';
        break;
    case 'calendario_equipo':
        if ($_SESSION['rol'] == 'admin') include 'views/rrhh/calendario.php';
        break;
    case 'estadisticas':
        if ($_SESSION['rol'] == 'admin') include 'views/rrhh/estadisticas.php';
        break;
    case 'jornada':
        include 'views/jornada.php';
        break;
    case 'solicitudes':
        include 'views/solicitudes.php';
        break;
    default:
        include 'views/dashboard.php';
        break;
}

include 'views/layout_footer.php';
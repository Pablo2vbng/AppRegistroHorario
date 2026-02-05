<?php
require_once 'config/config.php';

// Gestión de Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php"); exit();
    }
}

if (!isset($_SESSION['usuario_id'])) {
    include 'views/login.php';
    exit();
}

// ENRUTADOR SENCILLO
$pagina = isset($_GET['p']) ? $_GET['p'] : 'dashboard';

switch ($pagina) {
    case 'empleados':
        include 'views/rrhh/empleados.php';
        break;
    case 'jornada':
        include 'views/jornada.php'; // La crearemos después
        break;
    default:
        include 'views/dashboard.php';
        break;
}
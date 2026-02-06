<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email']; $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]); $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php"); exit();
    }
}

if (!isset($_SESSION['usuario_id'])) {
    include 'views/login.php'; exit();
}

$p = isset($_GET['p']) ? $_GET['p'] : 'dashboard';

include 'views/layout_header.php';

switch ($p) {
    case 'dashboard': include 'views/dashboard.php'; break;
    case 'empleados': if($_SESSION['rol'] == 'admin') include 'views/rrhh/empleados.php'; break;
    case 'empleado_detalle': if($_SESSION['rol'] == 'admin') include 'views/rrhh/empleado_detalle.php'; break;
    case 'informe_global': if($_SESSION['rol'] == 'admin') include 'views/rrhh/informe_global.php'; break; // NUEVA
    case 'informes_equipo': if($_SESSION['rol'] == 'admin') include 'views/rrhh/informes_equipo.php'; break;
    case 'gestion_ausencias': if($_SESSION['rol'] == 'admin') include 'views/rrhh/ausencias.php'; break;
    case 'calendario_anual': include 'views/calendario_anual.php'; break;
    case 'gestion_festivos': if($_SESSION['rol'] == 'admin') include 'views/rrhh/festivos.php'; break;
    case 'informe_legal': if($_SESSION['rol'] == 'admin') include 'views/rrhh/informe_legal.php'; break;
    case 'jornada': include 'views/jornada.php'; break;
    case 'solicitudes': include 'views/solicitudes.php'; break;
    case 'documentos': include 'views/documentos.php'; break;
    case 'perfil': include 'views/perfil.php'; break;
    default: include 'views/dashboard.php'; break;
}

include 'views/layout_footer.php';
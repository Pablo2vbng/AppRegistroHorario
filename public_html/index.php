<?php
require_once 'config/config.php';

// Si no hay sesión de usuario, cargar login (que crearemos luego)
if (!isset($_SESSION['usuario_id'])) {
    include 'views/login.php'; // Tendrás que crear este archivo en views/
    exit();
}

// Si está logueado, por defecto cargamos el dashboard
include 'views/dashboard.php';
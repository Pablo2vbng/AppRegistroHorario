<?php
// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u249173200_registroCvt');
define('DB_USER', 'u249173200_Pablo2vbngreg');
define('DB_PASS', 'Piramide73++%%');  

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}

// Configuración General
define('SITE_URL', 'https://registrohorariocvtools.innovai.es');
session_start();
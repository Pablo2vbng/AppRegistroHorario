<?php
// 1. Configuración de Zona Horaria (España - Benigànim)
date_default_timezone_set('Europe/Madrid');
setlocale(LC_TIME, 'es_ES.UTF-8');

// 2. Datos de la Empresa / Sede
define('EMPRESA_NOMBRE', 'CV TOOLS S.L.');
define('EMPRESA_SEDE', 'Benigànim (Valencia)');
define('GESTORA_NOMBRE', 'Carmen');
define('GESTORA_EMAIL', 'carmen@cvtools.es');
define('GESTORA_WHATSAPP', '34687166120');

// 3. Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u249173200_registroCvt');
define('DB_USER', 'u249173200_Pablo2vbngreg');
define('DB_PASS', 'Piramide73++%%'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Sincronizar hora
    $ahora = new DateTime();
    $offset = $ahora->format('P');
    $pdo->exec("SET time_zone='$offset';");

} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
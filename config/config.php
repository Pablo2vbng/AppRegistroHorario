<?php
// 1. Zona Horaria
date_default_timezone_set('Europe/Madrid');

// 2. PARÁMETROS DE EMPRESA
define('EMPRESA_NOMBRE', 'CV TOOLS S.L.');
define('EMPRESA_CIF', 'B-12345678');
define('EMPRESA_SEDE', 'Benigànim (Valencia)');
define('EMPRESA_DIRECCION', 'Polígono Industrial de Benigànim');
define('EMPRESA_LOGO', 'assets/img/logoCvTools.jpg');

// 3. COORDENADAS OFICINA (Pon aquí las reales de tu ubicación)
define('OFICINA_LAT', 38.939123); // <--- CAMBIA ESTO
define('OFICINA_LNG', -0.443456); // <--- CAMBIA ESTO
define('RADIO_PERMITIDO', 200);   // Metros de margen (precisión GPS)

// 4. RRHH
define('GESTORA_NOMBRE', 'Carmen');
define('GESTORA_EMAIL', 'carmen@cvtools.es');
define('GESTORA_WHATSAPP', '34687166120');

// 5. DB
define('DB_HOST', 'localhost');
define('DB_NAME', 'u249173200_registroCvt');
define('DB_USER', 'u249173200_Pablo2vbngreg');
define('DB_PASS', 'Piramide73++%%'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $ahora = new DateTime();
    $offset = $ahora->format('P');
    $pdo->exec("SET time_zone='$offset';");
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }
<?php
// 1. Zona Horaria
date_default_timezone_set('Europe/Madrid');

// 2. PARÁMETROS DE EMPRESA
define('EMPRESA_NOMBRE', '');
define('EMPRESA_CIF', '');
define('EMPRESA_SEDE', '');
define('EMPRESA_DIRECCION', '');
define('EMPRESA_LOGO', '');

// 3. COORDENADAS OFICINA  
define('OFICINA_LAT', );   
define('OFICINA_LNG', );   
define('RADIO_PERMITIDO', );    

// 4. RRHH
define('GESTORA_NOMBRE', '');
define('GESTORA_EMAIL', '');
define('GESTORA_WHATSAPP', '');

// 5. DB
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', ''); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $ahora = new DateTime();
    $offset = $ahora->format('P');
    $pdo->exec("SET time_zone='$offset';");
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

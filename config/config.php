<?php
// 1. Zona Horaria
date_default_timezone_set('Europe/Madrid');

// 2. PARÁMETROS DE EMPRESA
define('EMPRESA_NOMBRE', 'CV TOOLS S.L.');
define('EMPRESA_CIF', 'B-96573613');
define('EMPRESA_SEDE', 'Benigànim (Valencia)');
define('EMPRESA_DIRECCION', 'Avda. Camino de Albaida S/N');
define('EMPRESA_LOGO', 'assets/img/logoCvTools.jpg');

// 3. COORDENADAS OFICINA  
define('OFICINA_LAT', 38.939123);   
define('OFICINA_LNG', -0.443456);   
define('RADIO_PERMITIDO', 300);    

// 4. RRHH
define('GESTORA_NOMBRE', 'SuperAdmin');
define('GESTORA_EMAIL', 'carmen@cvtools.es');
define('GESTORA_WHATSAPP', '34687166120');

// 5. DB - Credenciales
define('DB_HOST', 'localhost');
define('DB_NAME', 'u249173200_registroCvt');
define('DB_USER', 'u249173200_Pablo2vbngreg');
define('DB_PASS', 'Piramide73++%%'); 

try {
    // Configuraciones de seguridad para PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Resultados como array asociativo
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva emulación para mayor seguridad SQL
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"        // Asegura UTF-8
    ];

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, $options);
    
    // Sincronizar la zona horaria de PHP con MySQL de forma segura
    $ahora = new DateTime();
    $offset = $ahora->format('P');
    $pdo->exec("SET time_zone='$offset';");

} catch (PDOException $e) {
    // En producción no mostramos el error detallado al usuario
    // Guardamos el error en el log del servidor para que tú puedas verlo
    error_log("Error de conexión BD: " . $e->getMessage());
    die("Error: No se ha podido conectar con el servicio. Por favor, inténtelo más tarde.");
}

// 6. Sesión
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
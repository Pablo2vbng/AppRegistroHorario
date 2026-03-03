<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Si la sesión ha caducado, respondemos con error en JSON para que el JS pueda avisar al usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión caducada. Por favor, inicia sesión de nuevo.']);
    exit();
}

/**
 * Función matemática para calcular distancia entre dos puntos GPS (Haversine)
 * @param float $lat1, $lon1 Coordenadas del usuario
 * @param float $lat2, $lon2 Coordenadas de la oficina (definidas en config.php)
 * @return float Distancia en metros
 */
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // Radio en metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['usuario_id'];
    
    // Sanitizamos las entradas para mayor seguridad
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);
    $lat  = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
    $lng  = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
    
    $fuera_rango = 0;
    
    // Solo calculamos si tenemos coordenadas válidas
    if($lat !== false && $lng !== false && $lat !== null && $lng !== null) {
        $distancia = calcularDistancia($lat, $lng, OFICINA_LAT, OFICINA_LNG);
        if($distancia > RADIO_PERMITIDO) {
            $fuera_rango = 1;
        }
    }

    try {
        $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        // Obtenemos la IP real incluso detrás de proxies de Hostinger
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        
        $stmt->execute([
            $user_id, 
            $tipo, 
            $lat, 
            $lng, 
            $fuera_rango, 
            $ip
        ]);

        echo json_encode([
            'success' => true, 
            'fuera' => (bool)$fuera_rango,
            'distancia_aprox' => isset($distancia) ? round($distancia, 2) : null
        ]);

    } catch (PDOException $e) {
        // Logueamos el error internamente y enviamos un mensaje genérico
        error_log("Error en fichaje (Usuario: $user_id): " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error al registrar el fichaje en la base de datos.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
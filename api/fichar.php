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
    
    // Sanitizamos el tipo
    $tipo = isset($_POST['tipo']) ? htmlspecialchars($_POST['tipo']) : '';
    
    // CORRECCIÓN 1: Evitar fallos de float por la configuración regional del servidor
    // Recogemos lat y lng, cambiamos posibles comas por puntos y forzamos a float numérico
    $lat_raw = isset($_POST['lat']) ? str_replace(',', '.', $_POST['lat']) : null;
    $lng_raw = isset($_POST['lng']) ? str_replace(',', '.', $_POST['lng']) : null;
    
    $lat = is_numeric($lat_raw) ? (float)$lat_raw : null;
    $lng = is_numeric($lng_raw) ? (float)$lng_raw : null;
    
    $fuera_rango = 0;
    
    // Solo calculamos si tenemos coordenadas válidas
    if($lat !== null && $lng !== null) {
        $distancia = calcularDistancia($lat, $lng, OFICINA_LAT, OFICINA_LNG);
        if($distancia > RADIO_PERMITIDO) {
            $fuera_rango = 1;
        }
    }

    try {
        // Mantenemos tu consulta original intacta
        $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        // CORRECCIÓN 2: Limpiar la IP. Hostinger a veces concatena IPs. Nos quedamos solo con la primera y le quitamos espacios.
        $ip_full = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = trim(explode(',', $ip_full)[0]); 
        
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
        // CORRECCIÓN 3: Exponemos el error real en la alerta para saber exactamente por qué se queja la base de datos
        error_log("Error en fichaje (Usuario: $user_id): " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error BD: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

// Función matemática para calcular distancia entre dos puntos GPS (Haversine)
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
    $tipo = $_POST['tipo'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    
    $fuera_rango = 0;
    if($lat && $lng) {
        $distancia = calcularDistancia($lat, $lng, OFICINA_LAT, OFICINA_LNG);
        if($distancia > RADIO_PERMITIDO) {
            $fuera_rango = 1;
        }
    }

    try {
        $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $tipo, $lat, $lng, $fuera_rango, $_SERVER['REMOTE_ADDR']]);
        echo json_encode(['success' => true, 'fuera' => $fuera_rango]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
<?php
// Incluimos tu configuración sin alterar nada del sistema actual
require_once __DIR__ . '/../config/config.php';

// Bloqueo de seguridad: Evita que cualquiera ejecute esto desde el navegador
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'CVTOOLS2026')) {
    die("Acceso denegado.");
}

$hoyDiaSemana = date('N'); // 1 (Lunes) a 7 (Domingo)
$ahora = date('H:i');
$fechaHoy = date('Y-m-d');

// 1. DEFINIMOS LOS HORARIOS (Tu regla general y las 4 excepciones)
$horarios = [
    'Arnau'  => ['entrada1' => '08:00', 'salida1' => '13:30', 'entrada2' => null,    'salida2' => null,    'dias' => [2, 4, 5]],
    'Sayo'   => ['entrada1' => '08:30', 'salida1' => '14:00', 'entrada2' => '15:00', 'salida2' => '17:30', 'dias' => [1, 2, 3, 4, 5]],
    'Rebeca' => ['entrada1' => '08:00', 'salida1' => '14:00', 'entrada2' => '15:00', 'salida2' => '17:00', 'dias' => [1, 2, 3, 4, 5]],
    'Pablo'  => ['entrada1' => '08:00', 'salida1' => '13:30', 'entrada2' => '15:30', 'salida2' => '18:00', 'dias' => [1, 2, 3, 4, 5]],
    'Default'=> ['entrada1' => '08:00', 'salida1' => '13:30', 'entrada2' => '15:00', 'salida2' => '17:30', 'dias' => [1, 2, 3, 4, 5]]
];

/**
 * Suma 5 minutos a la hora programada para dar el "margen de cortesía"
 */
function horaConRetraso($horaBase, $minutos = 5) {
    if (!$horaBase) return null;
    return date('H:i', strtotime("$horaBase + $minutos minutes"));
}

/**
 * Función que conecta con UltraMsg API para enviar WhatsApps
 */
function enviarWhatsApp($telefono, $mensaje) {
    if (empty($telefono)) return;

    // ==========================================
    // PON AQUÍ TUS CREDENCIALES DE ULTRAMSG
    // ==========================================
    $instanceId = "instance163987"; 
    $token = "wl6cwu850j0ib844"; 
    // ==========================================

    // Limpiamos el teléfono para dejar solo los números
    $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
    
    // Aseguramos que tenga el prefijo internacional de España (34) si el usuario solo puso 9 números
    if (strlen($telefonoLimpio) == 9) {
        $telefonoLimpio = "34" . $telefonoLimpio;
    } elseif (strpos($telefonoLimpio, '34') !== 0 && strlen($telefonoLimpio) > 9) {
        // Por si acaso pusieron prefijo sin + u otras variaciones, lo dejamos como esté
        $telefonoLimpio = "+" . $telefonoLimpio;
    } else {
        $telefonoLimpio = "+" . $telefonoLimpio;
    }

    $url = "https://api.ultramsg.com/" . $instanceId . "/messages/chat";
    $data = [
        'token' => $token,
        'to' => $telefonoLimpio,
        'body' => $mensaje
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Desactivamos verificación SSL temporalmente si el hosting compartido da problemas
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error cURL enviando WhatsApp a $telefonoLimpio: $error");
    } else {
        error_log("WhatsApp enviado a $telefonoLimpio. Respuesta: $response");
    }
}

try {
    $stmt = $pdo->query("SELECT id, nombre, telefono FROM usuarios WHERE rol = 'empleado'");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($empleados as $emp) {
        $nombreCorto = explode(' ', trim($emp['nombre']))[0];
        $miHorario = isset($horarios[$nombreCorto]) ? $horarios[$nombreCorto] : $horarios['Default'];

        if (!in_array($hoyDiaSemana, $miHorario['dias'])) continue;

        $limiteE1 = horaConRetraso($miHorario['entrada1']);
        $limiteS1 = horaConRetraso($miHorario['salida1']);
        $limiteE2 = horaConRetraso($miHorario['entrada2']);
        $limiteS2 = horaConRetraso($miHorario['salida2']);

        $stLast = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = ? ORDER BY id DESC LIMIT 1");
        $stLast->execute([$emp['id'], $fechaHoy]);
        $ultimoFichaje = $stLast->fetchColumn(); 

        $aviso = null;

        if ($ahora === $limiteE1) {
            if (!$ultimoFichaje) {
                $aviso = "🔔 Hola $nombreCorto, son las $limiteE1 y no consta tu fichaje de ENTRADA. ¿Te has olvidado?";
            }
        } elseif ($ahora === $limiteS1) {
            if ($ultimoFichaje === 'entrada' || $ultimoFichaje === 'reanudar') {
                $aviso = "🔔 Hola $nombreCorto, son las $limiteS1. Recuerda fichar tu PAUSA o SALIDA de la mañana.";
            }
        } elseif ($ahora === $limiteE2) {
            if ($ultimoFichaje === 'pausa') {
                $aviso = "🔔 Hola $nombreCorto, son las $limiteE2 y sigues en pausa. ¿Has olvidado REANUDAR tu jornada?";
            }
        } elseif ($ahora === $limiteS2) {
            if ($ultimoFichaje !== 'salida' && $ultimoFichaje !== false) {
                $aviso = "🔔 Hola $nombreCorto, son las $limiteS2. Tu jornada ha terminado, ¡no olvides fichar la SALIDA!";
            }
        }

        if ($aviso) {
            enviarWhatsApp($emp['telefono'], $aviso);
        }
    }
    
    echo "Revisión completada a las $ahora.";

} catch (Exception $e) {
    error_log("Error en cron de avisos: " . $e->getMessage());
}
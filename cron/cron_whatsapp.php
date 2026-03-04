<?php
// Incluimos tu configuración sin alterar nada del sistema actual
require_once __DIR__ . '/../config/config.php';

// Bloqueo de seguridad: Evita que cualquiera ejecute esto desde el navegador
// Para ejecutarlo manualmente desde la web, tendrías que poner: misitio.com/cron/cron_whatsapp.php?token=CVTOOLS2026
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'CVTOOLS2026')) {
    die("Acceso denegado.");
}

$hoyDiaSemana = date('N'); // 1 (Lunes) a 7 (Domingo)
$ahora = date('H:i');
$fechaHoy = date('Y-m-d');

// 1. DEFINIMOS LOS HORARIOS (Tu regla general y las 4 excepciones)
// Los días son: 1=L, 2=M, 3=X, 4=J, 5=V
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
 * Función que conecta con tu proveedor de WhatsApp
 */
function enviarWhatsApp($telefono, $mensaje) {
    if (empty($telefono)) return;

    /* =========================================================
       AQUÍ VA LA CONEXIÓN CON TU API DE WHATSAPP (Ej: UltraMsg)
       =========================================================
       Ejemplo de código estándar (descomentar al tener la API):
       
       $url = "https://api.ultramsg.com/TU_INSTANCE_ID/messages/chat";
       $data = [
           'token' => 'TU_TOKEN_AQUI',
           'to' => $telefono,
           'body' => $mensaje
       ];
       
       $ch = curl_init($url);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       $response = curl_exec($ch);
       curl_close($ch);
    */

    // Mientras no conectes la API, guardamos un registro para comprobar que la lógica funciona
    error_log("WhatsApp simulado a $telefono: $mensaje");
}

try {
    // Obtenemos todos los empleados
    $stmt = $pdo->query("SELECT id, nombre, telefono FROM usuarios WHERE rol = 'empleado'");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($empleados as $emp) {
        // Cogemos solo el primer nombre (ej: "Arnau Garcia" -> "Arnau")
        $nombreCorto = explode(' ', trim($emp['nombre']))[0];
        
        // Asignamos su horario específico o el general por defecto
        $miHorario = isset($horarios[$nombreCorto]) ? $horarios[$nombreCorto] : $horarios['Default'];

        // Si hoy no es su día de trabajo (ej: Arnau los lunes), lo saltamos
        if (!in_array($hoyDiaSemana, $miHorario['dias'])) continue;

        // Calculamos las horas a las que debe saltar el aviso (Hora base + 5 mins)
        $limiteE1 = horaConRetraso($miHorario['entrada1']);
        $limiteS1 = horaConRetraso($miHorario['salida1']);
        $limiteE2 = horaConRetraso($miHorario['entrada2']);
        $limiteS2 = horaConRetraso($miHorario['salida2']);

        // Obtenemos qué ha sido lo ÚLTIMO que ha fichado este usuario HOY
        $stLast = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = ? ORDER BY id DESC LIMIT 1");
        $stLast->execute([$emp['id'], $fechaHoy]);
        $ultimoFichaje = $stLast->fetchColumn(); // Devolverá 'entrada', 'pausa', 'reanudar', 'salida' o false si no hay nada

        $aviso = null;

        // LÓGICA DE DETECCIÓN (Si es la hora límite y no están en el estado correcto)
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

        // Si hay un aviso que mandar, disparamos la función
        if ($aviso) {
            // Asegúrate de que el campo "telefono" en tu base de datos tenga el formato internacional (ej: +34600123456)
            enviarWhatsApp($emp['telefono'], $aviso);
        }
    }
    
    echo "Revisión completada a las $ahora.";

} catch (Exception $e) {
    error_log("Error en cron de avisos: " . $e->getMessage());
}
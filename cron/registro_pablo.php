<?php
// Incluimos la configuración del sistema
require_once __DIR__ . '/../config/config.php';

// Bloqueo de seguridad: Evita ejecución desde el navegador sin token
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'CVTOOLS2026')) {
    die("Acceso denegado.");
}

$hoyDiaSemana = date('N'); // 1 (Lunes) a 7 (Domingo)
$fechaHoy = date('Y-m-d');
$ahora = date('H:i');

// 1. COMPROBAR DÍAS LABORABLES (De Lunes a Viernes)
if ($hoyDiaSemana > 5) {
    die("Hoy es fin de semana. No se ficha.");
}

// 2. DEFINIR USUARIOS AUTOMATIZADOS Y SUS HORARIOS
$usuarios_auto = [
    'Pablo' => [
        'entrada1' => '07:00',
        'salida1'  => '15:00'
    ],
    'Rebeca' => [
        'entrada1' => '06:00',
        'salida1'  => '14:00'
    ]
];

try {
    //prueba
    // 3. COMPROBAR FESTIVOS GENERALES (Se hace una vez para todos)
    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
    $stF->execute([$fechaHoy]);
    if ($stF->fetch()) {
        die("Hoy es festivo. No se ficha.");
    }

    // FUNCIÓN PARA INSERTAR EL FICHAJE
    function insertarFichajeAuto($pdo, $usuarioId, $tipo, $fechaHoraSimulada) {
        $stCheck = $pdo->prepare("SELECT id FROM fichajes WHERE usuario_id = ? AND tipo = ? AND DATE(fecha_hora) = CURDATE()");
        $stCheck->execute([$usuarioId, $tipo]);
        
        if (!$stCheck->fetch()) {
            // Coordenadas reales fijas y captura de error estricta
            $lat = 38.9390000;
            $lng = -0.4470000;

            $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro, fecha_hora) 
                    VALUES (?, ?, ?, ?, 0, '127.0.0.1 (Auto)', ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$usuarioId, $tipo, $lat, $lng, $fechaHoraSimulada])) {
                return true;
            } else {
                throw new Exception("Fallo en BD al insertar $tipo: " . implode(" | ", $stmt->errorInfo()));
            }
        }
        return false;
    }

    $mensajes = [];

    // 4. PROCESAR A CADA USUARIO DEL LISTADO
    foreach ($usuarios_auto as $nombre => $horario) {
        
        // Buscar ID del usuario
        $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE nombre LIKE ? LIMIT 1");
        $stmtU->execute(["%$nombre%"]);
        $userId = $stmtU->fetchColumn();

        if (!$userId) {
            $mensajes[] = "<span style='color:orange;'>⚠️ No se encontró al usuario $nombre en la base de datos.</span>";
            continue; // Saltamos al siguiente usuario
        }

        // Comprobar vacaciones o ausencias aprobadas para este usuario específico
        $stV = $pdo->prepare("SELECT id FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND ? BETWEEN fecha_inicio AND fecha_fin");
        $stV->execute([$userId, $fechaHoy]);
        if ($stV->fetch()) {
            $mensajes[] = "<span style='color:blue;'>ℹ️ $nombre está de vacaciones o baja. No se ficha.</span>";
            continue;
        }

        // GENERAR HORAS ALEATORIAS DETERMINISTAS
        // Sumamos el largo del nombre a la semilla para que los minutos calculados sean distintos entre Pablo y Rebeca
        $seed = (int) date('Ymd') + strlen($nombre);
        srand($seed);

        // Entrada Mañana: Rango 10 a 15 mins ANTES
        $minutosE1 = rand(-15, -10);
        $horaEntrada1 = date('H:i', strtotime($horario['entrada1'] . " $minutosE1 minutes"));

        // Salida Mañana (Pausa): Rango 5 mins ANTES a 5 mins DESPUÉS
        $minutosS1 = rand(-5, 5);
        $horaSalida1 = date('H:i', strtotime($horario['salida1'] . " $minutosS1 minutes"));

        // Entrada Tarde (Reanudar): Rango 10 a 15 mins ANTES
        $minutosE2 = rand(-15, -10);
        $horaEntrada2 = date('H:i', strtotime($horario['entrada2'] . " $minutosE2 minutes"));

        // Salida Tarde (Salida Definitiva): Rango 5 mins ANTES a 5 mins DESPUÉS
        $minutosS2 = rand(-5, 5);
        $horaSalida2 = date('H:i', strtotime($horario['salida2'] . " $minutosS2 minutes"));

        srand(); // Restauramos semilla

        // LÓGICA DE DETECCIÓN ACUMULATIVA PARA EL USUARIO ACTUAL
        $accionesUsuario = 0;

        if ($ahora >= $horaEntrada1) {
            if (insertarFichajeAuto($pdo, $userId, 'entrada', "$fechaHoy $horaEntrada1:00")) $accionesUsuario++;
        }
        if ($ahora >= $horaSalida1) {
            if (insertarFichajeAuto($pdo, $userId, 'pausa', "$fechaHoy $horaSalida1:00")) $accionesUsuario++;
        }
        if ($ahora >= $horaEntrada2) {
            if (insertarFichajeAuto($pdo, $userId, 'reanudar', "$fechaHoy $horaEntrada2:00")) $accionesUsuario++;
        }
        if ($ahora >= $horaSalida2) {
            if (insertarFichajeAuto($pdo, $userId, 'salida', "$fechaHoy $horaSalida2:00")) $accionesUsuario++;
        }

        if ($accionesUsuario > 0) {
            $mensajes[] = "<span style='color:green;'>✅ ÉXITO REAL: Se han recuperado/insertado $accionesUsuario fichajes de $nombre.</span>";
        } else {
            $mensajes[] = "<span style='color:gray;'>ℹ️ Revisión completada. $nombre está al día para la hora actual.</span>";
        }
    }

    // Imprimir el reporte final de la ejecución
    echo "<h3>Resultados de sincronización ($ahora):</h3>";
    echo implode("<br><br>", $mensajes);

} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ ERROR DETECTADO: " . $e->getMessage() . "</h3>";
}
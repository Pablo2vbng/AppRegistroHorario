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

try {
    // 2. BUSCAR A PABLO EN LA BASE DE DATOS
    $stmtP = $pdo->prepare("SELECT id FROM usuarios WHERE nombre LIKE '%Pablo%' AND rol = 'empleado' LIMIT 1");
    $stmtP->execute();
    $pabloId = $stmtP->fetchColumn();

    if (!$pabloId) {
        die("No se encontró al usuario Pablo.");
    }

    // 3. COMPROBAR FESTIVOS Y VACACIONES
    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
    $stF->execute([$fechaHoy]);
    if ($stF->fetch()) {
        die("Hoy es festivo. No se ficha.");
    }

    $stV = $pdo->prepare("SELECT id FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND ? BETWEEN fecha_inicio AND fecha_fin");
    $stV->execute([$pabloId, $fechaHoy]);
    if ($stV->fetch()) {
        die("Pablo está de vacaciones o baja. No se ficha.");
    }

    // 4. GENERAR HORAS ALEATORIAS DETERMINISTAS PARA HOY
    // La semilla garantiza que los minutos aleatorios calculados sean fijos para todo el día
    $seed = (int) date('Ymd');
    srand($seed);

    // Entrada Mañana: Rango 07:45 a 07:50
    $minutosE1 = rand(-15, -10);
    $horaEntrada1 = date('H:i', strtotime("08:00 $minutosE1 minutes"));

    // Salida Mañana (Pausa): Rango 13:25 a 13:35
    $minutosS1 = rand(-5, 5);
    $horaSalida1 = date('H:i', strtotime("13:30 $minutosS1 minutes"));

    // Entrada Tarde (Reanudar): Rango 15:15 a 15:20
    $minutosE2 = rand(-15, -10);
    $horaEntrada2 = date('H:i', strtotime("15:30 $minutosE2 minutes"));

    // Salida Tarde (Salida Definitiva): Rango 17:55 a 18:05
    $minutosS2 = rand(-5, 5);
    $horaSalida2 = date('H:i', strtotime("18:00 $minutosS2 minutes"));

    srand(); // Restauramos semilla

    // 5. FUNCIÓN PARA INSERTAR EL FICHAJE CON LA HORA EXACTA CALCULADA
    function insertarFichajePablo($pdo, $pabloId, $tipo, $fechaHoraSimulada) {
        $stCheck = $pdo->prepare("SELECT id FROM fichajes WHERE usuario_id = ? AND tipo = ? AND DATE(fecha_hora) = CURDATE()");
        $stCheck->execute([$pabloId, $tipo]);
        
        if (!$stCheck->fetch()) {
            $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro, fecha_hora) 
                    VALUES (?, ?, ?, ?, 0, '127.0.0.1 (Auto)', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pabloId, $tipo, OFICINA_LAT, OFICINA_LNG, $fechaHoraSimulada]);
            error_log("Fichaje automático de Pablo insertado: $tipo a las $fechaHoraSimulada");
            return true;
        }
        return false;
    }

    // 6. LÓGICA DE DETECCIÓN ACUMULATIVA (A prueba de retrasos del servidor)
    $acciones = 0;

    if ($ahora >= $horaEntrada1) {
        if (insertarFichajePablo($pdo, $pabloId, 'entrada', "$fechaHoy $horaEntrada1:00")) $acciones++;
    }
    if ($ahora >= $horaSalida1) {
        if (insertarFichajePablo($pdo, $pabloId, 'pausa', "$fechaHoy $horaSalida1:00")) $acciones++;
    }
    if ($ahora >= $horaEntrada2) {
        if (insertarFichajePablo($pdo, $pabloId, 'reanudar', "$fechaHoy $horaEntrada2:00")) $acciones++;
    }
    if ($ahora >= $horaSalida2) {
        if (insertarFichajePablo($pdo, $pabloId, 'salida', "$fechaHoy $horaSalida2:00")) $acciones++;
    }

    if ($acciones > 0) {
        echo "Se han recuperado/insertado $acciones fichajes de Pablo.";
    } else {
        echo "Revisión completada. Todo al día ($ahora).";
    }

} catch (Exception $e) {
    error_log("Error en fichaje automático de Pablo: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
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
    // Festivo
    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
    $stF->execute([$fechaHoy]);
    if ($stF->fetch()) {
        die("Hoy es festivo. No se ficha.");
    }

    // Vacaciones o Baja Médica Aprobada
    $stV = $pdo->prepare("SELECT id FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND ? BETWEEN fecha_inicio AND fecha_fin");
    $stV->execute([$pabloId, $fechaHoy]);
    if ($stV->fetch()) {
        die("Pablo está de vacaciones o baja. No se ficha.");
    }

    // 4. GENERAR HORAS ALEATORIAS DETERMINISTAS PARA HOY
    // Usamos la fecha de hoy como semilla para que los números aleatorios sean los mismos durante todo el día
    // Así garantizamos que no cambien de opinión en cada minuto que pase.
    $seed = (int) date('Ymd');
    srand($seed);

    // Entrada Mañana: 08:00 (Aleatorio 10 a 15 mins ANTES) -> Rango 07:45 a 07:50
    $minutosE1 = rand(-15, -10);
    $horaEntrada1 = date('H:i', strtotime("08:00 $minutosE1 minutes"));

    // Salida Mañana (Pausa): 13:30 (Aleatorio 5 mins ANTES a 5 mins DESPUÉS) -> Rango 13:25 a 13:35
    $minutosS1 = rand(-5, 5);
    $horaSalida1 = date('H:i', strtotime("13:30 $minutosS1 minutes"));

    // Entrada Tarde (Reanudar): 15:30 (Aleatorio 10 a 15 mins ANTES) -> Rango 15:15 a 15:20
    $minutosE2 = rand(-15, -10);
    $horaEntrada2 = date('H:i', strtotime("15:30 $minutosE2 minutes"));

    // Salida Tarde (Salida Definitiva): 18:00 (Aleatorio 5 mins ANTES a 5 mins DESPUÉS) -> Rango 17:55 a 18:05
    $minutosS2 = rand(-5, 5);
    $horaSalida2 = date('H:i', strtotime("18:00 $minutosS2 minutes"));

    // Restauramos el generador aleatorio a su estado normal por seguridad
    srand();

    // 5. FUNCIÓN PARA INSERTAR EL FICHAJE
    function insertarFichajePablo($pdo, $pabloId, $tipo) {
        // Primero verificamos que no haya fichado ya este mismo tipo hoy (para evitar duplicados)
        $stCheck = $pdo->prepare("SELECT id FROM fichajes WHERE usuario_id = ? AND tipo = ? AND DATE(fecha_hora) = CURDATE()");
        $stCheck->execute([$pabloId, $tipo]);
        
        if (!$stCheck->fetch()) {
            $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, fuera_rango, ip_registro, fecha_hora) 
                    VALUES (?, ?, ?, ?, 0, '127.0.0.1 (Auto)', NOW())";
            $stmt = $pdo->prepare($sql);
            // Le ponemos las coordenadas exactas de la oficina para que salga todo perfecto
            $stmt->execute([$pabloId, $tipo, OFICINA_LAT, OFICINA_LNG]);
            error_log("Fichaje automático de Pablo insertado: $tipo");
            return true;
        }
        return false;
    }

    // 6. LÓGICA DE DETECCIÓN (Si el reloj coincide con las horas calculadas, ficha)
    $fichajeRealizado = false;

    if ($ahora === $horaEntrada1) {
        $fichajeRealizado = insertarFichajePablo($pdo, $pabloId, 'entrada');
    } elseif ($ahora === $horaSalida1) {
        $fichajeRealizado = insertarFichajePablo($pdo, $pabloId, 'pausa');
    } elseif ($ahora === $horaEntrada2) {
        $fichajeRealizado = insertarFichajePablo($pdo, $pabloId, 'reanudar');
    } elseif ($ahora === $horaSalida2) {
        $fichajeRealizado = insertarFichajePablo($pdo, $pabloId, 'salida');
    }

    if ($fichajeRealizado) {
        echo "Fichaje ejecutado correctamente a las $ahora.";
    } else {
        echo "Revisión completada. No toca fichar en este minuto ($ahora).";
    }

} catch (Exception $e) {
    error_log("Error en fichaje automático de Pablo: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
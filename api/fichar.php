<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $pdo->beginTransaction();

        // 1. Insertar el fichaje actual
        $sql = "INSERT INTO fichajes (usuario_id, tipo, latitud, longitud, ip_registro) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $tipo, $lat, $lng, $ip]);

        // 2. LÓGICA DE RECUPERACIÓN AUTOMÁTICA (Solo al Salir)
        if ($tipo == 'salida') {
            // Obtener todos los fichajes de hoy para este usuario
            $stmtF = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY fecha_hora ASC");
            $stmtF->execute([$user_id]);
            $eventos = $stmtF->fetchAll();

            $segundosTotales = 0; $inicioTramo = null;
            foreach($eventos as $ev) {
                if($ev['tipo'] == 'entrada' || $ev['tipo'] == 'reanudar') $inicioTramo = strtotime($ev['fecha_hora']);
                if(($ev['tipo'] == 'pausa' || $ev['tipo'] == 'salida') && $inicioTramo) {
                    $segundosTotales += (strtotime($ev['fecha_hora']) - $inicioTramo);
                    $inicioTramo = null;
                }
            }

            $jornadaEstablecida = 8 * 3600; // 8 horas en segundos
            if ($segundosTotales > $jornadaEstablecida) {
                $segundosExtras = $segundosTotales - $jornadaEstablecida;
                $diasARecuperar = $segundosExtras / (8 * 3600); // Convertimos segundos extra a fracción de día

                // Sumamos el tiempo extra a la bolsa de vacaciones
                $stmtRecup = $pdo->prepare("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles + ? WHERE id = ?");
                $stmtRecup->execute([$diasARecuperar, $user_id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en DB: ' . $e->getMessage()]);
    }
}
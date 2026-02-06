<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $estado = $_POST['estado'];

    try {
        $pdo->beginTransaction();

        // 1. Obtener datos de la solicitud
        $stmtS = $pdo->prepare("SELECT * FROM ausencias WHERE id = ?");
        $stmtS->execute([$id]);
        $solicitud = $stmtS->fetch();

        if ($estado == 'aprobado' && $solicitud['tipo'] == 'vacaciones' && $solicitud['estado'] != 'aprobado') {
            // 2. Calcular días laborables (Lunes a Viernes)
            $inicio = new DateTime($solicitud['fecha_inicio']);
            $fin = new DateTime($solicitud['fecha_fin']);
            $fin->modify('+1 day'); // Incluir el último día
            $intervalo = new DateInterval('P1D');
            $periodo = new DatePeriod($inicio, $intervalo, $fin);
            
            $diasARestar = 0;
            foreach ($periodo as $dt) {
                if ($dt->format('N') < 6) { // 1 a 5 son Lunes a Viernes
                    // Comprobar si ese día ya es festivo en Benigànim para no restarlo doble
                    $stmtF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
                    $stmtF->execute([$dt->format('Y-m-d')]);
                    if (!$stmtF->fetch()) {
                        $diasARestar++;
                    }
                }
            }

            // 3. Restar de la bolsa del usuario
            $stmtU = $pdo->prepare("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles - ? WHERE id = ?");
            $stmtU->execute([$diasARestar, $solicitud['usuario_id']]);
        }

        // 4. Actualizar estado de la solicitud
        $stmt = $pdo->prepare("UPDATE ausencias SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
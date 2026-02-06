<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $recuperable = $_POST['recuperable'] ?? 0;

    try {
        $pdo->beginTransaction();

        $stmtS = $pdo->prepare("SELECT * FROM ausencias WHERE id = ?");
        $stmtS->execute([$id]);
        $sol = $stmtS->fetch();

        // Si Carmen aprueba y NO es recuperable, restamos de vacaciones
        if ($estado == 'aprobado' && $recuperable == 0 && ($sol['tipo'] == 'vacaciones' || $sol['tipo'] == 'personal')) {
            $inicio = new DateTime($sol['fecha_inicio']);
            $fin = new DateTime($sol['fecha_fin']);
            $fin->modify('+1 day');
            $periodo = new DatePeriod($inicio, new DateInterval('P1D'), $fin);
            
            $dias = 0;
            foreach ($periodo as $dt) {
                // Solo restamos de lunes a viernes y que no sean festivos
                if ($dt->format('N') < 6) {
                    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
                    $stF->execute([$dt->format('Y-m-d')]);
                    if (!$stF->fetch()) $dias++;
                }
            }
            $pdo->prepare("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles - ? WHERE id = ?")
                ->execute([$dias, $sol['usuario_id']]);
        }

        $stmt = $pdo->prepare("UPDATE ausencias SET estado = ?, recuperable = ? WHERE id = ?");
        $stmt->execute([$estado, $recuperable, $id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
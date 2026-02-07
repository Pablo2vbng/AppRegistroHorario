<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if ($_SESSION['rol'] != 'admin') exit();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id']; $estado = $_POST['estado']; $recuperable = $_POST['recuperable'] ?? 0;

    try {
        $pdo->beginTransaction();
        $stmtS = $pdo->prepare("SELECT * FROM ausencias WHERE id = ?");
        $stmtS->execute([$id]); $sol = $stmtS->fetch();

        // Lógica de saldo de vacaciones si es aprobado y SÍ es recuperable
        if ($estado == 'aprobado' && $recuperable == 1) {
            $diasARestar = 0;
            if ($sol['es_por_horas']) {
                $diasARestar = $sol['horas_solicitadas'] / 8;
            } else {
                $inicio = new DateTime($sol['fecha_inicio']);
                $fin = new DateTime($sol['fecha_fin']);
                $fin->modify('+1 day');
                foreach (new DatePeriod($inicio, new DateInterval('P1D'), $fin) as $dt) {
                    if ($dt->format('N') < 6) {
                        $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
                        $stF->execute([$dt->format('Y-m-d')]);
                        if (!$stF->fetch()) $diasARestar++;
                    }
                }
            }
            $pdo->prepare("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles - ? WHERE id = ?")
                ->execute([$diasARestar, $sol['usuario_id']]);
        }

        // Actualizamos estado y reseteamos la vista de notificación para que el usuario reciba el aviso
        $pdo->prepare("UPDATE ausencias SET estado = ?, recuperable = ?, notificacion_vista = 0 WHERE id = ?")
            ->execute([$estado, $recuperable, $id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
}
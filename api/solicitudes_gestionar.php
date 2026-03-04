<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Seguridad: Solo administradores
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $estado = $_POST['estado'] ?? ''; // 'aprobado' o 'rechazado'
    $recuperable = isset($_POST['recuperable']) ? (int)$_POST['recuperable'] : 0;

    if (!$id || empty($estado)) {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Obtener los datos de la ausencia
        $stmtS = $pdo->prepare("SELECT * FROM ausencias WHERE id = ? FOR UPDATE");
        $stmtS->execute([$id]);
        $sol = $stmtS->fetch();

        if (!$sol) {
            throw new Exception("Solicitud no encontrada");
        }

        // 2. Lógica de saldo de vacaciones
        // CUMPLE PUNTO 3 y 4: Al exigir $recuperable == 1, los permisos médicos (0) no restan vacaciones.
        if ($estado == 'aprobado' && $sol['estado'] != 'aprobado' && $recuperable == 1) {
            $diasARestar = 0;

            if ($sol['es_por_horas']) {
                // Conversión de horas a días (jornada estándar de 8h)
                $diasARestar = $sol['horas_solicitadas'] / 8;
            } else {
                $inicio = new DateTime($sol['fecha_inicio']);
                $fin = new DateTime($sol['fecha_fin']);
                $fin->modify('+1 day'); // Para incluir el último día en el periodo

                $intervalo = new DateInterval('P1D');
                $periodo = new DatePeriod($inicio, $intervalo, $fin);

                foreach ($periodo as $dt) {
                    if ($dt->format('N') < 6) {
                        // Comprobar si ese día es festivo
                        $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
                        $stF->execute([$dt->format('Y-m-d')]);
                        if (!$stF->fetch()) {
                            $diasARestar++;
                        }
                    }
                }
            }

            // Actualizar saldo del usuario
            $stmtUpdateUser = $pdo->prepare("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles - ? WHERE id = ?");
            $stmtUpdateUser->execute([$diasARestar, $sol['usuario_id']]);
        }
        
        // 4. Actualizar estado de la solicitud y resetear notificación para el empleado
        $stmtUpdateAusencia = $pdo->prepare("UPDATE ausencias SET estado = ?, recuperable = ?, notificacion_vista = 0 WHERE id = ?");
        $stmtUpdateAusencia->execute([$estado, $recuperable, $id]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al gestionar solicitud: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
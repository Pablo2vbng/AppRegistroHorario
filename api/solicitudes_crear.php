<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Verificación de sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $tipo = $_POST['tipo'] ?? '';
    $inicio = $_POST['fecha_inicio'] ?? null;
    
    // CORRECCIÓN DE FECHAS (Mantenemos tu lógica original)
    $es_por_horas = isset($_POST['es_por_horas']) ? 1 : 0;
    
    if ($es_por_horas) {
        $fin = $inicio; // Si es por horas, fin es el mismo día
    } else {
        $fin = (!empty($_POST['fecha_fin'])) ? $_POST['fecha_fin'] : $inicio;
    }

    $permuta_trabajo = !empty($_POST['fecha_permuta_trabajo']) ? $_POST['fecha_permuta_trabajo'] : null;
    $horas = ($es_por_horas) ? ($_POST['horas_solicitadas'] ?? null) : null;
    $motivo = $_POST['motivo'] ?? ''; 
    $ruta_archivo = null;

    // LÓGICA DE SUBIDA DE JUSTIFICANTE (Mejorada con validación de tipo)
    if (isset($_FILES['justificante']) && $_FILES['justificante']['error'] == 0) {
        $uploadDir = '../uploads/justificantes/';
        
        // Crear carpeta si no existe con permisos seguros
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileNameOriginal = $_FILES['justificante']['name'];
        $extension = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));
        
        // Validar extensiones permitidas para evitar archivos ejecutables
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        if (in_array($extension, $allowedExtensions)) {
            $fileName = "BAJA_" . $usuario_id . "_" . time() . "." . $extension;
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['justificante']['tmp_name'], $targetFile)) {
                $ruta_archivo = 'uploads/justificantes/' . $fileName;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Formato de archivo no permitido (solo imágenes o PDF)']);
            exit();
        }
    }

    try {
        // Insertamos en la tabla ausencias manteniendo todos tus campos
        $sql = "INSERT INTO ausencias (
                    usuario_id, 
                    tipo, 
                    fecha_inicio, 
                    fecha_fin, 
                    fecha_permuta_trabajo, 
                    es_por_horas, 
                    horas_solicitadas, 
                    motivo, 
                    archivo_justificante
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $usuario_id, 
            $tipo, 
            $inicio, 
            $fin, 
            $permuta_trabajo, 
            $es_por_horas, 
            $horas, 
            $motivo, 
            $ruta_archivo
        ]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        // Logueamos el error y respondemos de forma segura
        error_log("Error al crear solicitud: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar la solicitud. Contacte con soporte.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
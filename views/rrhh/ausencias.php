<?php
// Obtener todas las ausencias pendientes con el nombre del empleado
$sql = "SELECT a.*, u.nombre as empleado_nombre 
        FROM ausencias a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.estado = 'pendiente' 
        ORDER BY a.fecha_inicio ASC";
$ausencias = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Ausencias - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">

    <!-- Sidebar (Copia el de empleados.php o usa el nuevo nav) -->

    <div class="flex-1 p-8">
        <header class="mb-8">
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Validación de Ausencias</h1>
            <p class="text-slate-500 font-medium">Revisa y aprueba las solicitudes del equipo</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($ausencias as $a): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-1 rounded uppercase tracking-widest">
                            <?php echo $a['tipo']; ?>
                        </span>
                        <span class="text-slate-400 text-xs font-bold"><?php echo date('d/m/Y', strtotime($a['fecha_inicio'])); ?></span>
                    </div>
                    <h3 class="font-black text-slate-800 text-lg mb-1"><?php echo $a['empleado_nombre']; ?></h3>
                    <p class="text-slate-500 text-sm italic mb-4">"<?php echo $a['motivo'] ?: 'Sin comentarios'; ?>"</p>
                    <div class="bg-slate-50 rounded-xl p-3 text-sm font-bold text-slate-600 border border-slate-100">
                        <i class="far fa-calendar-alt mr-2 text-blue-500"></i>
                        <?php echo date('d M', strtotime($a['fecha_inicio'])); ?> al <?php echo date('d M', strtotime($a['fecha_fin'])); ?>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button onclick="gestionar(<?php echo $a['id']; ?>, 'aprobado')" class="flex-1 bg-emerald-500 text-white font-black py-3 rounded-xl hover:bg-emerald-600 transition shadow-lg shadow-emerald-100">
                        APROBAR
                    </button>
                    <button onclick="gestionar(<?php echo $a['id']; ?>, 'rechazado')" class="flex-1 bg-white text-rose-500 border-2 border-rose-500 font-black py-3 rounded-xl hover:bg-rose-50 transition">
                        RECHAZAR
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($ausencias)): ?>
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-mug-hot text-slate-200 text-6xl mb-4"></i>
                <p class="text-slate-400 font-bold italic">No hay solicitudes pendientes. ¡Todo al día!</p>
                <a href="index.php" class="text-blue-500 underline text-sm mt-2 block">Volver al escritorio</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function gestionar(id, estado) {
        if(!confirm('¿Estás seguro de que quieres ' + estado + ' esta solicitud?')) return;
        
        fetch('api/solicitudes_gestionar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&estado=${estado}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert("Error: " + data.message);
        });
    }
    </script>
</body>
</html>
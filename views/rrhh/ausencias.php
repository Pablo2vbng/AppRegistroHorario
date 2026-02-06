<?php
// 1. Obtener todas las ausencias pendientes de validar
$sql = "SELECT a.*, u.nombre as empleado_nombre 
        FROM ausencias a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.estado = 'pendiente' 
        ORDER BY a.fecha_inicio ASC";
$ausencias = $pdo->query($sql)->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <header class="mb-10 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Validación de Ausencias</h1>
            <p class="text-slate-500 font-bold">Solicitudes pendientes de aprobación</p>
        </div>
        <div class="bg-amber-100 text-amber-700 px-4 py-2 rounded-xl text-xs font-black uppercase">
            <?php echo count($ausencias); ?> Pendientes
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach($ausencias as $a): ?>
        <div class="bg-white rounded-[32px] shadow-sm border border-slate-200 p-8 flex flex-col justify-between hover:shadow-xl transition-shadow duration-300">
            <div>
                <div class="flex justify-between items-start mb-6">
                    <span class="bg-slate-100 text-slate-500 text-[10px] font-black px-3 py-1 rounded-lg uppercase tracking-widest border border-slate-200">
                        <?php echo $a['tipo']; ?>
                    </span>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Solicitado</p>
                        <p class="text-xs font-bold text-slate-600"><?php echo date('d/m/Y', strtotime($a['fecha_inicio'])); ?></p>
                    </div>
                </div>

                <h3 class="font-black text-slate-800 text-xl mb-2"><?php echo $a['empleado_nombre']; ?></h3>
                
                <?php if($a['motivo']): ?>
                    <p class="text-slate-500 text-sm italic mb-6">"<?php echo $a['motivo']; ?>"</p>
                <?php endif; ?>

                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-6">
                    <div class="flex items-center text-slate-700 font-bold text-sm">
                        <i class="far fa-calendar-check mr-3 text-blue-500 text-lg"></i>
                        <span><?php echo date('d M', strtotime($a['fecha_inicio'])); ?> al <?php echo date('d M', strtotime($a['fecha_fin'])); ?></span>
                    </div>
                </div>

                <!-- BOTÓN PARA VER JUSTIFICANTE MÉDICO -->
                <?php if($a['archivo_justificante']): ?>
                    <a href="<?php echo $a['archivo_justificante']; ?>" target="_blank" 
                       class="flex items-center justify-center w-full bg-rose-50 text-rose-600 p-3 rounded-xl mb-6 font-black text-[10px] uppercase tracking-widest border border-rose-100 hover:bg-rose-100 transition">
                        <i class="fas fa-file-medical mr-2 text-sm"></i> Ver Justificante Médico
                    </a>
                <?php endif; ?>
            </div>

            <div class="flex gap-4">
                <button onclick="gestionarSolicitud(<?php echo $a['id']; ?>, 'aprobado')" class="flex-1 bg-emerald-500 text-white font-black py-4 rounded-2xl hover:bg-emerald-600 shadow-lg shadow-emerald-100 transition active:scale-95 text-xs">
                    APROBAR
                </button>
                <button onclick="gestionarSolicitud(<?php echo $a['id']; ?>, 'rechazado')" class="flex-1 bg-white text-rose-500 border-2 border-rose-500 font-black py-4 rounded-2xl hover:bg-rose-50 transition active:scale-95 text-xs">
                    RECHAZAR
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($ausencias)): ?>
        <div class="col-span-full py-32 text-center bg-white rounded-[40px] border-2 border-dashed border-slate-200">
            <i class="fas fa-check-circle text-slate-100 text-8xl mb-6"></i>
            <p class="text-slate-400 font-black uppercase italic tracking-widest">Bandeja de entrada vacía. ¡Buen trabajo Carmen!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function gestionarSolicitud(id, estado) {
    if(!confirm('¿Deseas confirmar esta acción?')) return;
    
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
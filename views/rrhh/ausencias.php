<?php
if ($_SESSION['rol'] != 'admin') exit();
$ausencias = $pdo->query("SELECT a.*, u.nombre as emp FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'pendiente' ORDER BY a.id ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20">
    <div class="mb-12 flex justify-between items-end px-2">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Bandeja de Validación</h1>
            <p class="text-slate-400 font-bold text-sm">Gestionar permisos, vacaciones y bajas de equipo</p>
        </div>
        <div class="bg-slate-900 text-white px-5 py-2 rounded-2xl font-black text-xs uppercase shadow-xl"><?php echo count($ausencias); ?> pendientes</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach($ausencias as $a): 
            $icon = ($a['tipo']=='medico')?'fa-hospital':(($a['tipo']=='vacaciones')?'fa-umbrella-beach':'fa-exchange-alt');
        ?>
        <div class="bg-white p-8 rounded-[45px] shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-xl transition-all duration-300">
            <div>
                <div class="flex justify-between items-start mb-6">
                    <span class="bg-slate-100 text-slate-400 text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest border"><?php echo $a['tipo']; ?></span>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-tighter"><?php echo date('d/m/Y', strtotime($a['fecha_inicio'])); ?></p>
                </div>
                
                <h3 class="text-xl font-black text-slate-800 uppercase italic mb-4"><?php echo $a['emp']; ?></h3>

                <!-- INFO DETALLADA CORREGIDA -->
                <div class="bg-slate-50 p-5 rounded-3xl mb-6 space-y-2">
                    <div class="flex items-center text-xs font-bold text-slate-600">
                        <i class="fas <?php echo $icon; ?> mr-3 text-blue-500"></i>
                        <?php if($a['tipo'] == 'permuta'): ?>
                            Libre <?php echo date('d/m', strtotime($a['fecha_inicio'])); ?> → Trabaja <?php echo date('d/m', strtotime($a['fecha_permuta_trabajo'])); ?>
                        <?php elseif($a['es_por_horas']): ?>
                            Día <?php echo date('d/m', strtotime($a['fecha_inicio'])); ?> <span class="text-blue-600 ml-1 font-black">(<?php echo $a['horas_solicitadas']; ?>h solicitadas)</span>
                        <?php else: ?>
                            <?php echo date('d/m', strtotime($a['fecha_inicio'])); ?> al <?php echo date('d/m', strtotime($a['fecha_fin'])); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BOTÓN VER BAJA MÉDICA -->
                <?php if($a['archivo_justificante']): ?>
                    <a href="<?php echo $a['archivo_justificante']; ?>" target="_blank" class="flex items-center justify-center w-full bg-rose-500 text-white p-4 rounded-2xl mb-6 font-black text-[10px] uppercase tracking-widest shadow-lg shadow-rose-100 hover:bg-rose-600 transition">
                        <i class="fas fa-file-pdf mr-2"></i> Descargar Parte de Baja
                    </a>
                <?php endif; ?>

                <?php if($a['motivo']): ?>
                    <p class="text-[10px] text-slate-400 italic mb-6">"<?php echo $a['motivo']; ?>"</p>
                <?php endif; ?>

                <!-- CONFIGURACIÓN ADMIN -->
                <div class="bg-slate-900 text-white p-6 rounded-3xl mb-8">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-widest">¿Es recuperable?</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="recup_<?php echo $a['id']; ?>" class="sr-only peer">
                            <div class="w-12 h-6 bg-slate-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <button onclick="validar(<?php echo $a['id']; ?>, 'aprobado')" class="flex-1 bg-emerald-500 text-white font-black py-4 rounded-3xl shadow-lg uppercase text-[10px] tracking-widest active:scale-95 transition">Aprobar</button>
                <button onclick="validar(<?php echo $a['id']; ?>, 'rechazado')" class="flex-1 bg-white text-rose-500 border-2 border-rose-500 font-black py-4 rounded-3xl uppercase text-[10px] tracking-widest active:scale-95 transition">Denegar</button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($ausencias)): ?>
            <div class="col-span-full py-32 text-center bg-white rounded-[50px] border-2 border-dashed border-slate-200">
                <i class="fas fa-check-double text-slate-100 text-8xl mb-6"></i>
                <p class="text-slate-400 font-black uppercase italic tracking-widest text-xs">No hay peticiones pendientes en Benigànim</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function validar(id, estado) {
    const isRecup = document.getElementById('recup_'+id).checked ? 1 : 0;
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/solicitudes_gestionar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&estado=${estado}&recuperable=${isRecup}`
    }).then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Completado', text: 'El trabajador ha sido notificado.', confirmButtonColor: '#000' }).then(() => location.reload());
        }
    });
}
</script>
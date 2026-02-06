<?php
$ausencias = $pdo->query("SELECT a.*, u.nombre as emp FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'pendiente' ORDER BY a.id ASC")->fetchAll();
?>

<div class="max-w-5xl mx-auto pb-20">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Bandeja RRHH Benigànim</h1>
        <p class="text-slate-400 font-bold text-sm">Validación de tiempos recuperables y vacaciones</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach($ausencias as $a): ?>
        <div class="bg-white p-8 rounded-[45px] shadow-sm border border-slate-200">
            <div class="flex justify-between items-start mb-6">
                <span class="bg-slate-900 text-white text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest"><?php echo $a['tipo']; ?></span>
                <p class="text-xs font-black text-slate-400 uppercase"><?php echo date('d/m/Y', strtotime($a['fecha_inicio'])); ?></p>
            </div>
            
            <h3 class="text-xl font-black text-slate-800 uppercase italic mb-6"><?php echo $a['emp']; ?></h3>

            <?php if($a['es_por_horas']): ?>
                <div class="bg-blue-50 p-4 rounded-2xl mb-6 border border-blue-100 flex items-center justify-between">
                    <span class="text-xs font-black text-blue-600 uppercase">Ausencia Corta</span>
                    <span class="text-lg font-black text-blue-800"><?php echo $a['horas_solicitadas']; ?> Horas</span>
                </div>
            <?php endif; ?>

            <div class="bg-slate-50 p-6 rounded-3xl mb-8 flex flex-col items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-4 tracking-widest">¿Debe recuperar este tiempo?</p>
                <div class="flex items-center gap-4">
                    <span class="text-[9px] font-black text-slate-400 uppercase">NO</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="recup_<?php echo $a['id']; ?>" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                    <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest">SÍ</span>
                </div>
            </div>

            <div class="flex gap-4">
                <button onclick="procesar(<?php echo $a['id']; ?>, 'aprobado')" class="flex-1 bg-emerald-500 text-white font-black py-4 rounded-3xl shadow-lg uppercase text-[10px] tracking-widest active:scale-95 transition">Aceptar</button>
                <button onclick="procesar(<?php echo $a['id']; ?>, 'rechazado')" class="flex-1 bg-white text-rose-500 border-2 border-rose-500 font-black py-4 rounded-3xl uppercase text-[10px] tracking-widest active:scale-95 transition">Denegar</button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($ausencias)): ?>
            <div class="col-span-full py-32 text-center bg-white rounded-[50px] border-2 border-dashed border-slate-200">
                <i class="fas fa-check-circle text-slate-100 text-8xl mb-6"></i>
                <p class="text-slate-400 font-black uppercase italic tracking-widest text-xs">Carmen, no hay más trabajo pendiente hoy.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function procesar(id, estado) {
    const isRecup = document.getElementById('recup_'+id).checked ? 1 : 0;
    
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/solicitudes_gestionar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&estado=${estado}&recuperable=${isRecup}`
    })
    .then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Registro Actualizado', text: 'El trabajador verá el estado en su panel.', confirmButtonColor: '#000' })
            .then(() => location.reload());
        }
    });
}
</script>
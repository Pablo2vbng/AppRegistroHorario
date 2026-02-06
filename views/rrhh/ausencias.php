<?php
$ausencias = $pdo->query("SELECT a.*, u.nombre as emp FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'pendiente' ORDER BY a.fecha_inicio ASC")->fetchAll();
?>

<div class="max-w-5xl mx-auto">
    <div class="mb-10">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic tracking-tighter">Bandeja de Validación</h1>
        <p class="text-slate-400 font-bold text-sm">Carmen, aquí decides qué es recuperable y qué no.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach($ausencias as $a): ?>
        <div class="bg-white p-8 rounded-[45px] shadow-sm border border-slate-200">
            <div class="flex justify-between items-start mb-6">
                <span class="bg-slate-100 text-slate-400 text-[10px] font-black px-3 py-1 rounded-xl uppercase border"><?php echo $a['tipo']; ?></span>
                <p class="text-xs font-black text-slate-900"><?php echo date('d/m/Y', strtotime($a['fecha_inicio'])); ?></p>
            </div>
            
            <h3 class="text-xl font-black text-slate-800 uppercase italic mb-2"><?php echo $a['emp']; ?></h3>
            <p class="text-slate-500 text-sm mb-6 italic">"<?php echo $a['motivo'] ?: 'Sin comentarios'; ?>"</p>

            <!-- DECISIÓN DE CARMEN -->
            <div class="bg-slate-50 p-6 rounded-3xl mb-8">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-4 tracking-widest text-center">Configuración de la aprobación</p>
                <div class="flex items-center justify-center gap-4">
                    <span class="text-[10px] font-black uppercase text-slate-400">¿Es recuperable?</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="recup_<?php echo $a['id']; ?>" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>
                <p class="text-[9px] text-center text-slate-300 mt-4 uppercase italic">Si marcas SI, no se restarán días de sus vacaciones.</p>
            </div>

            <div class="flex gap-4">
                <button onclick="validar(<?php echo $a['id']; ?>, 'aprobado')" class="flex-1 bg-emerald-500 text-white font-black py-4 rounded-3xl shadow-lg uppercase text-xs">Aprobar</button>
                <button onclick="validar(<?php echo $a['id']; ?>, 'rechazado')" class="flex-1 bg-white text-rose-500 border-2 border-rose-500 font-black py-4 rounded-3xl uppercase text-xs">Denegar</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function validar(id, estado) {
    const recup = document.getElementById('recup_'+id).checked ? 1 : 0;
    fetch('api/solicitudes_gestionar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&estado=${estado}&recuperable=${recup}`
    })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
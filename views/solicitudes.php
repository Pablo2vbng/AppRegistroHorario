<?php
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY fecha_inicio DESC");
$stmt->execute([$usuario_id]);
$mis_solicitudes = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-slate-200 sticky top-10">
                <h2 class="text-xl font-black text-slate-800 mb-8 uppercase italic tracking-tighter">Nueva Solicitud</h2>
                <form id="formSolicitud" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest">Tipo de Petición</label>
                        <select name="tipo" class="w-full bg-slate-50 border border-slate-100 p-4 rounded-2xl font-bold text-slate-700 outline-none">
                            <option value="vacaciones">🏖️ Vacaciones</option>
                            <option value="permuta">🔄 Permuta (Cambio día laborable)</option>
                            <option value="medico">🏥 Asistencia Médica</option>
                            <option value="personal">👤 Asuntos Propios</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        <input type="date" name="fecha_fin" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3">Detalles para Carmen</label>
                        <textarea name="motivo" rows="4" class="w-full bg-slate-50 border border-slate-100 p-5 rounded-2xl outline-none text-sm" placeholder="Explica aquí tu cambio de día o motivo..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-3xl shadow-xl uppercase text-[10px] tracking-widest transition active:scale-95">Enviar Petición</button>
                </form>
                <div id="status-msg" class="mt-4 text-center text-xs font-bold"></div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-4">Estado de mis Solicitudes</h2>
            <?php foreach($mis_solicitudes as $sol): 
                $statusColor = ['pendiente' => 'bg-amber-100 text-amber-700', 'aprobado' => 'bg-emerald-100 text-emerald-700', 'rechazado' => 'bg-rose-100 text-rose-700'];
            ?>
            <div class="bg-white p-6 rounded-[35px] border border-slate-100 flex items-center justify-between shadow-sm group hover:shadow-lg transition">
                <div class="flex items-center space-x-6">
                    <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-xl text-slate-400">
                        <i class="fas <?php echo ($sol['tipo']=='permuta')?'fa-exchange-alt':'fa-umbrella-beach'; ?>"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 uppercase text-[10px] tracking-widest"><?php echo $sol['tipo']; ?></p>
                        <p class="text-slate-500 font-bold text-sm"><?php echo date('d M', strtotime($sol['fecha_inicio'])); ?> - <?php echo date('d M', strtotime($sol['fecha_fin'])); ?></p>
                    </div>
                </div>
                <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest <?php echo $statusColor[$sol['estado']]; ?>">
                    <?php echo $sol['estado']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/solicitudes_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
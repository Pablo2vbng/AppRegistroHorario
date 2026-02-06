<?php
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY fecha_inicio DESC");
$stmt->execute([$usuario_id]);
$mis_solicitudes = $stmt->fetchAll();
?>

<div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- FORMULARIO -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 sticky top-10">
                <h2 class="text-xl font-black text-slate-800 mb-6 uppercase italic tracking-tighter">Nueva Solicitud</h2>
                <form id="formSolicitud" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Tipo de ausencia</label>
                        <select name="tipo" id="tipo_ausencia" onchange="checkMedico(this.value)" class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl font-bold text-slate-700 outline-none">
                            <option value="vacaciones">🏝️ Vacaciones</option>
                            <option value="medico">🏥 Asistencia Médica / Baja</option>
                            <option value="personal">👤 Asuntos Propios</option>
                        </select>
                    </div>

                    <div id="campo_justificante" class="hidden animate-bounce">
                        <label class="block text-[10px] font-black text-rose-500 uppercase mb-2">Adjuntar Justificante (PDF/JPG)</label>
                        <input type="file" name="justificante" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-2xl shadow-xl transition active:scale-95 uppercase text-xs tracking-widest">
                        Enviar a Carmen
                    </button>
                </form>
                <div id="status-msg" class="mt-4 text-center text-xs font-bold"></div>
            </div>
        </div>

        <!-- HISTORIAL -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-black text-slate-800 uppercase italic mb-4">Mis Estados</h2>
            <?php foreach($mis_solicitudes as $sol): 
                $statusColor = ['pendiente' => 'bg-amber-100 text-amber-700', 'aprobado' => 'bg-emerald-100 text-emerald-700', 'rechazado' => 'bg-rose-100 text-rose-700'];
            ?>
            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-slate-50 rounded-xl text-slate-400">
                        <i class="fas <?php echo ($sol['tipo'] == 'medico') ? 'fa-hospital' : 'fa-umbrella-beach'; ?>"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-700 uppercase text-[10px]"><?php echo $sol['tipo']; ?></p>
                        <p class="text-xs text-slate-400 font-bold"><?php echo $sol['fecha_inicio']; ?> al <?php echo $sol['fecha_fin']; ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if($sol['archivo_justificante']): ?>
                        <a href="<?php echo $sol['archivo_justificante']; ?>" target="_blank" class="text-blue-500 hover:text-blue-700" title="Ver justificante"><i class="fas fa-file-medical text-lg"></i></a>
                    <?php endif; ?>
                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?php echo $statusColor[$sol['estado']]; ?>">
                        <?php echo $sol['estado']; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function checkMedico(val) {
    document.getElementById('campo_justificante').classList.toggle('hidden', val !== 'medico');
}

document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    const msg = document.getElementById('status-msg');
    msg.innerText = "Procesando...";
    
    fetch('api/solicitudes_crear.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) location.reload();
        else {
            msg.className = "mt-4 text-center text-rose-500 font-bold text-xs";
            msg.innerText = "Error: " + data.message;
        }
    });
}
</script>
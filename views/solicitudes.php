<?php
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY fecha_inicio DESC");
$stmt->execute([$usuario_id]);
$mis_solicitudes = $stmt->fetchAll();
?>

<div class="max-w-5xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 sticky top-10">
                <h2 class="text-xl font-black text-slate-800 mb-6 uppercase italic tracking-tighter">Nueva Solicitud</h2>
                <form id="formSolicitud" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Tipo de ausencia</label>
                        <select name="tipo" class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                            <option value="vacaciones">🏝️ Vacaciones</option>
                            <option value="medico">🏥 Asistencia Médica</option>
                            <option value="personal">👤 Asuntos Propios</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" required class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Comentarios para Carmen</label>
                        <textarea name="motivo" rows="3" class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl outline-none text-sm" placeholder="Escribe aquí..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-2xl shadow-xl transition active:scale-95 uppercase text-xs tracking-widest">
                        Enviar a Validación
                    </button>
                </form>
                <div id="status-msg" class="mt-4 text-center text-xs font-bold"></div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: HISTORIAL -->
        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter flex items-center">
                <i class="fas fa-history mr-3 text-slate-300"></i> Mis Solicitudes
            </h2>

            <?php foreach($mis_solicitudes as $sol): 
                $statusColor = [
                    'pendiente' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'aprobado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'rechazado' => 'bg-rose-100 text-rose-700 border-rose-200'
                ];
                $icon = ($sol['tipo'] == 'vacaciones') ? 'fa-umbrella-beach text-blue-400' : 'fa-user-clock text-slate-400';
            ?>
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:shadow-md transition">
                <div class="flex items-center space-x-5">
                    <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-xl">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 uppercase text-xs tracking-widest"><?php echo $sol['tipo']; ?></p>
                        <p class="text-slate-500 font-bold text-sm">
                            <?php echo date('d M', strtotime($sol['fecha_inicio'])); ?> — <?php echo date('d M', strtotime($sol['fecha_fin'])); ?>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border <?php echo $statusColor[$sol['estado']]; ?>">
                        <?php echo $sol['estado']; ?>
                    </span>
                    <p class="text-[10px] text-slate-300 mt-2 italic"><?php echo $sol['motivo'] ? substr($sol['motivo'],0,20).'...' : ''; ?></p>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($mis_solicitudes)): ?>
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <p class="text-slate-400 font-bold italic uppercase text-xs tracking-widest">No has realizado ninguna solicitud</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    const msg = document.getElementById('status-msg');
    msg.className = "mt-4 text-center text-blue-500 text-xs font-bold";
    msg.innerText = "Enviando solicitud...";
    
    fetch('api/solicitudes_crear.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            msg.className = "mt-4 text-center text-rose-500 text-xs font-bold";
            msg.innerText = "Error: " + data.message;
        }
    });
}
</script>
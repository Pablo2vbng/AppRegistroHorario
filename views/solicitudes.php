<?php
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY id DESC");
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
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3">Tipo</label>
                        <select name="tipo" id="tipo" onchange="toggleHoras(this.value)" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-slate-700 outline-none">
                            <option value="vacaciones">🏖️ Vacaciones</option>
                            <option value="medico">🏥 Asistencia Médica</option>
                            <option value="personal">👤 Asuntos Propios</option>
                            <option value="permuta">🔄 Permuta</option>
                        </select>
                    </div>

                    <div id="check_horas_div" class="hidden">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="es_por_horas" id="es_por_horas" onchange="inputHoras(this.checked)" class="w-5 h-5 rounded border-slate-300">
                            <span class="text-xs font-black text-slate-600 uppercase">¿Es solo por unas horas?</span>
                        </label>
                    </div>

                    <div id="rango_fechas">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Periodo</label>
                        <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold mb-2">
                        <input type="date" name="fecha_fin" id="f_fin" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                    </div>

                    <div id="campo_horas" class="hidden">
                        <label class="block text-[10px] font-black text-blue-500 uppercase mb-2">¿Cuántas horas necesitas?</label>
                        <input type="number" name="horas_solicitadas" step="0.5" placeholder="Ej: 2.5" class="w-full bg-blue-50 border-2 border-blue-100 p-4 rounded-2xl font-black text-blue-700">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-black py-5 rounded-3xl shadow-xl uppercase text-[10px] tracking-widest active:scale-95 transition">Enviar Petición</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-black text-slate-800 uppercase italic mb-4">Mis Estados</h2>
            <?php foreach($mis_solicitudes as $sol): ?>
            <div class="bg-white p-6 rounded-[30px] border border-slate-100 flex items-center justify-between shadow-sm">
                <div>
                    <p class="font-black text-slate-800 uppercase text-[10px] tracking-widest"><?php echo $sol['tipo']; ?></p>
                    <p class="text-slate-500 font-bold text-sm">
                        <?php echo date('d M', strtotime($sol['fecha_inicio'])); ?> 
                        <?php echo $sol['es_por_horas'] ? "({$sol['horas_solicitadas']}h)" : "al ".date('d M', strtotime($sol['fecha_fin'])); ?>
                    </p>
                </div>
                <span class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest <?php echo ($sol['estado']=='pendiente')?'bg-amber-100 text-amber-600':'bg-emerald-100 text-emerald-600'; ?>">
                    <?php echo $sol['estado']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleHoras(val) {
    const div = document.getElementById('check_horas_div');
    div.classList.toggle('hidden', val === 'vacaciones');
    if(val === 'vacaciones') {
        document.getElementById('es_por_horas').checked = false;
        inputHoras(false);
    }
}

function inputHoras(check) {
    document.getElementById('campo_horas').classList.toggle('hidden', !check);
    document.getElementById('f_fin').classList.toggle('hidden', check);
}

document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Enviando...', text: 'Estamos procesando tu petición', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/solicitudes_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: '¡Petición Enviada!', text: 'Carmen recibirá tu notificación ahora mismo.', confirmButtonColor: '#2563eb' })
            .then(() => location.reload());
        }
    });
}
</script>
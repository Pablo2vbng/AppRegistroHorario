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
                        <select name="tipo" id="tipo_peticion" onchange="actualizarEtiquetas(this.value)" class="w-full bg-slate-50 border border-slate-100 p-4 rounded-2xl font-bold text-slate-700 outline-none">
                            <option value="vacaciones">🏖️ Vacaciones</option>
                            <option value="permuta">🔄 Permuta (Cambio de día)</option>
                            <option value="medico">🏥 Asistencia Médica</option>
                            <option value="personal">👤 Asuntos Propios</option>
                        </select>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label id="lbl_fecha_1" class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Inicio / Día Libre</label>
                            <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>
                        
                        <div id="contenedor_fecha_2">
                            <label id="lbl_fecha_2" class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" id="input_fecha_fin" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>

                        <div id="contenedor_permuta_trabajo" class="hidden animate-in slide-in-from-top">
                            <label class="block text-[10px] font-black text-emerald-500 uppercase mb-2">Día que trabajaré a cambio</label>
                            <input type="date" name="fecha_permuta_trabajo" class="w-full bg-emerald-50 border-2 border-emerald-100 p-4 rounded-2xl font-bold text-emerald-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Detalles para Carmen</label>
                        <textarea name="motivo" rows="3" class="w-full bg-slate-50 border border-slate-100 p-4 rounded-2xl outline-none text-sm"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-3xl shadow-xl uppercase text-[10px] tracking-widest">Enviar Petición</button>
                </form>
                <div id="status-msg" class="mt-4 text-center text-xs font-bold"></div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-black text-slate-800 uppercase italic mb-4 tracking-tighter">Historial de Solicitudes</h2>
            <?php foreach($mis_solicitudes as $sol): 
                $statusColor = ['pendiente' => 'bg-amber-100 text-amber-700', 'aprobado' => 'bg-emerald-100 text-emerald-700', 'rechazado' => 'bg-rose-100 text-rose-700'];
            ?>
            <div class="bg-white p-6 rounded-[35px] border border-slate-100 flex items-center justify-between shadow-sm group hover:shadow-lg transition-all duration-300">
                <div class="flex items-center space-x-6">
                    <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-xl text-slate-300 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                        <i class="fas <?php echo ($sol['tipo']=='permuta')?'fa-exchange-alt':'fa-umbrella-beach'; ?>"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 uppercase text-[10px] tracking-widest"><?php echo $sol['tipo']; ?></p>
                        <p class="text-slate-500 font-bold text-sm">
                            <?php echo date('d M', strtotime($sol['fecha_inicio'])); ?> 
                            <?php if($sol['tipo'] == 'permuta'): ?>
                                <i class="fas fa-arrow-right mx-2 text-[10px]"></i> trabaja el <?php echo date('d M', strtotime($sol['fecha_permuta_trabajo'])); ?>
                            <?php else: ?>
                                al <?php echo date('d M', strtotime($sol['fecha_fin'])); ?>
                            <?php endif; ?>
                        </p>
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
function actualizarEtiquetas(tipo) {
    const lbl1 = document.getElementById('lbl_fecha_1');
    const lbl2 = document.getElementById('lbl_fecha_2');
    const contFin = document.getElementById('contenedor_fecha_2');
    const contPermuta = document.getElementById('contenedor_permuta_trabajo');

    if(tipo === 'permuta') {
        lbl1.innerText = "Día que NO trabajaré";
        contFin.classList.add('hidden');
        contPermuta.classList.remove('hidden');
        document.getElementById('input_fecha_fin').required = false;
    } else {
        lbl1.innerText = "Fecha Inicio";
        lbl2.innerText = "Fecha Fin";
        contFin.classList.remove('hidden');
        contPermuta.classList.add('hidden');
        document.getElementById('input_fecha_fin').required = true;
    }
}

document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/solicitudes_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
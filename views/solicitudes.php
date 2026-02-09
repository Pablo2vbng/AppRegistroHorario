<?php
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario_id]);
$mis_solicitudes = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <div class="lg:col-span-1">
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-slate-200 sticky top-10">
                <h2 class="text-xl font-black text-slate-800 mb-8 uppercase italic tracking-tighter">Nueva Solicitud</h2>
                <form id="formSolicitud" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3">Tipo de Petición</label>
                        <select name="tipo" id="tipo" onchange="toggleForm(this.value)" class="w-full bg-slate-50 border border-slate-100 p-4 rounded-2xl font-bold text-slate-700 outline-none">
                            <option value="vacaciones">🏖️ Vacaciones</option>
                            <option value="medico">🏥 Asistencia Médica / Baja</option>
                            <option value="personal">👤 Asuntos Propios</option>
                            <option value="permuta">🔄 Permuta</option>
                        </select>
                    </div>

                    <div id="div_archivo" class="hidden animate-in fade-in zoom-in duration-300">
                        <label class="block text-[10px] font-black text-rose-500 uppercase mb-3 italic">Adjuntar Parte de Baja / Justificante</label>
                        <div class="relative group">
                            <input type="file" name="justificante" class="w-full text-xs text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer">
                        </div>
                    </div>

                    <div id="check_horas_div" class="hidden">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="es_por_horas" id="es_por_horas" onchange="inputHoras(this.checked)" class="w-5 h-5 rounded border-slate-300">
                            <span class="text-xs font-black text-slate-600 uppercase">¿Es solo por unas horas?</span>
                        </label>
                    </div>

                    <div id="rango_fechas" class="space-y-4">
                        <div>
                            <label id="lbl_f1" class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>
                        <div id="div_f2">
                            <label id="lbl_f2" class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" id="input_f2" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                        </div>
                        <div id="div_permuta" class="hidden">
                            <label class="block text-[10px] font-black text-emerald-500 uppercase mb-2 italic">Día que trabajaré a cambio</label>
                            <input type="date" name="fecha_permuta_trabajo" class="w-full bg-emerald-50 border-2 border-emerald-100 p-4 rounded-2xl font-bold text-emerald-700">
                        </div>
                    </div>

                    <div id="div_horas" class="hidden">
                        <label class="block text-[10px] font-black text-blue-500 uppercase mb-2">Horas solicitadas</label>
                        <input type="number" name="horas_solicitadas" step="0.5" placeholder="Ej: 3.5" class="w-full bg-blue-50 border-2 border-blue-100 p-4 rounded-2xl font-black text-blue-700">
                    </div>

                    <!-- CAMPO DE COMENTARIOS RECUPERADO -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Motivo / Comentarios</label>
                        <textarea name="motivo" rows="3" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-slate-700 outline-none resize-none" placeholder="Opcional..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-black py-5 rounded-3xl shadow-xl uppercase text-[10px] tracking-widest transition hover:bg-slate-900 active:scale-95">Enviar Petición</button>
                </form>
            </div>
        </div>

        <!-- COLUMNA DERECHA: HISTORIAL -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-black text-slate-800 uppercase italic mb-4 tracking-tighter">Historial de Solicitudes</h2>
            <?php foreach($mis_solicitudes as $sol): 
                $statusColor = ['pendiente' => 'bg-amber-100 text-amber-600 border-amber-200', 'aprobado' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'rechazado' => 'bg-rose-100 text-rose-700 border-rose-200'];
            ?>
            <div class="bg-white p-6 rounded-[35px] border border-slate-100 flex items-center justify-between shadow-sm group hover:shadow-lg transition-all duration-300">
                <div class="flex items-center space-x-6">
                    <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-xl text-slate-300 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                        <i class="fas <?php echo ($sol['tipo']=='medico')?'fa-file-medical':(($sol['tipo']=='vacaciones')?'fa-umbrella-beach':'fa-exchange-alt'); ?>"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 uppercase text-[10px] tracking-widest"><?php echo $sol['tipo']; ?></p>
                        <p class="text-slate-500 font-bold text-sm">
                            <?php echo date('d M Y', strtotime($sol['fecha_inicio'])); ?> 
                            
                            <?php if($sol['es_por_horas']): ?>
                                <span class="text-blue-600 ml-1 font-black"> (<?php echo $sol['horas_solicitadas']; ?>h)</span>
                            <?php elseif($sol['tipo'] == 'permuta'): ?>
                                <i class="fas fa-arrow-right mx-2 text-[10px] text-slate-300"></i> Trabaja el <?php echo date('d M', strtotime($sol['fecha_permuta_trabajo'])); ?>
                            <?php else: ?>
                                al <?php echo date('d M Y', strtotime($sol['fecha_fin'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php if($sol['archivo_justificante']): ?>
                        <a href="<?php echo $sol['archivo_justificante']; ?>" target="_blank" class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-rose-500 hover:bg-rose-500 hover:text-white transition shadow-sm" title="Ver Baja">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    <?php endif; ?>
                    <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border <?php echo $statusColor[$sol['estado']]; ?>">
                        <?php echo $sol['estado']; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleForm(val) {
    document.getElementById('div_archivo').classList.toggle('hidden', val !== 'medico');
    document.getElementById('check_horas_div').classList.toggle('hidden', val === 'vacaciones');
    document.getElementById('div_permuta').classList.toggle('hidden', val !== 'permuta');
    
    const esPorHoras = document.getElementById('es_por_horas').checked;
    document.getElementById('div_f2').classList.toggle('hidden', val === 'permuta' || esPorHoras);
    
    const lbl1 = document.getElementById('lbl_f1');
    lbl1.innerText = (val === 'permuta') ? "Día que NO trabajaré" : "Fecha Inicio";
}

function inputHoras(check) {
    document.getElementById('div_horas').classList.toggle('hidden', !check);
    document.getElementById('div_f2').classList.toggle('hidden', check);
}

document.getElementById('formSolicitud').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/solicitudes_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: '¡Petición Enviada!', text: 'RRHH la recibirá en su bandeja.', confirmButtonColor: '#0f172a' })
            .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}
</script>
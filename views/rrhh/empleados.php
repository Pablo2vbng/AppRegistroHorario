<?php
if ($_SESSION['rol'] != 'admin') exit();
// Excluimos dueños de la lista de gestión de trabajadores para mayor claridad
$empleados = $pdo->query("SELECT * FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20 px-2">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Plantilla de Equipo</h1>
            <p class="text-slate-400 font-bold text-sm italic">Gestión de jornadas, saldos y accesos</p>
        </div>
        <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 text-white px-8 py-4 rounded-[30px] font-black shadow-xl uppercase text-xs tracking-widest active:scale-95 transition">
            <i class="fas fa-user-plus mr-2"></i> Nuevo Trabajador
        </button>
    </header>

    <!-- VISTA MÓVIL: CARDS -->
    <div class="grid grid-cols-1 gap-4 md:hidden">
        <?php foreach($empleados as $emp): 
            $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
            $jsData = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="bg-white p-6 rounded-[40px] shadow-sm border border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="<?php echo $foto; ?>" class="w-14 h-14 rounded-[20px] object-cover shadow-md">
                <div>
                    <p class="font-black text-slate-800 uppercase text-xs"><?php echo $emp['nombre']; ?></p>
                    <p class="text-[9px] font-bold text-blue-500 uppercase italic"><?php echo $emp['horas_jornada']; ?>h/día</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick='abrirModalEditar(<?php echo $jsData; ?>)' class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100">
                    <i class="fas fa-edit text-xs"></i>
                </button>
                <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-eye text-xs"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- VISTA PC: TABLA -->
    <div class="hidden md:block bg-white rounded-[50px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b text-[10px] font-black uppercase text-slate-400">
                <tr><th class="p-8">Empleado</th><th class="p-8">Jornada Contrato</th><th class="p-8 text-center">Saldo Vac.</th><th class="p-8 text-right">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($empleados as $emp): 
                    $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
                    $jsData = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-8 flex items-center">
                        <img src="<?php echo $foto; ?>" class="w-12 h-12 rounded-2xl object-cover mr-5 shadow-sm">
                        <div>
                            <p class="font-black text-slate-700 uppercase italic"><?php echo $emp['nombre']; ?></p>
                            <p class="text-[10px] text-slate-400 font-bold"><?php echo $emp['email']; ?></p>
                        </div>
                    </td>
                    <td class="p-8">
                        <p class="font-black text-slate-800"><?php echo $emp['horas_jornada']; ?> Horas / día</p>
                        <p class="text-[9px] text-slate-400 uppercase font-black mt-1 italic">Días: <?php echo str_replace(['1','2','3','4','5','6','7'],['L','M','X','J','V','S','D'],$emp['dias_laborables']); ?></p>
                    </td>
                    <td class="p-8 text-center">
                        <span class="text-blue-600 font-black text-lg"><?php echo round($emp['dias_vacaciones_disponibles'], 1); ?></span>
                        <span class="text-slate-300 text-[10px] font-bold"> / <?php echo $emp['dias_vacaciones_totales']; ?></span>
                    </td>
                    <td class="p-8 text-right space-x-3">
                        <button onclick='abrirModalEditar(<?php echo $jsData; ?>)' class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl hover:bg-amber-500 hover:text-white transition shadow-sm border border-amber-100"><i class="fas fa-user-edit"></i></button>
                        <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="inline-block w-10 h-10 bg-slate-100 text-slate-400 rounded-xl hover:bg-slate-900 hover:text-white transition shadow-sm border border-slate-200 flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR EMPLEADO (CARGA TODOS LOS CAMPOS) -->
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-xl overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-amber-500 p-8 text-white flex justify-between items-center italic">
            <h3 class="font-black uppercase tracking-widest text-[10px]">Configurar Trabajador</h3>
            <button onclick="toggleModalEditar(false)" class="text-2xl">&times;</button>
        </div>
        <form id="formEditarEmpleado" class="p-8 md:p-12 space-y-6 overflow-y-auto max-h-[85vh]">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nombre Completo</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-amber-500/10">
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Email Corporativo</label>
                    <input type="email" name="email" id="edit_email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-amber-500/10">
                </div>
            </div>

            <div class="bg-slate-50 p-6 rounded-[35px] border border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="text-[9px] font-black text-blue-600 uppercase ml-2">Horas por jornada</label>
                        <input type="number" step="0.5" name="horas_jornada" id="edit_horas" class="w-full bg-white border p-4 rounded-2xl font-black text-blue-700">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Tipo Horario</label>
                        <select name="horario" id="edit_horario" class="w-full bg-white border p-4 rounded-2xl font-bold">
                            <option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Partido">Partido</option><option value="Flexible">Flexible</option>
                        </select>
                    </div>
                </div>
                
                <label class="block text-[9px] font-black text-slate-400 uppercase mb-3 ml-2">Días Laborales (Check para trabajar)</label>
                <div class="flex flex-wrap gap-2 justify-center">
                    <?php $dArr = ['1'=>'L','2'=>'M','3'=>'X','4'=>'J','5'=>'V','6'=>'S','7'=>'D']; foreach($dArr as $v => $l): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="dias[]" value="<?php echo $v; ?>" id="dia_<?php echo $v; ?>" class="hidden peer">
                            <div class="w-10 h-10 rounded-xl border-2 border-slate-200 flex items-center justify-center font-black text-xs text-slate-300 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all"><?php echo $l; ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100">
                <div>
                    <label class="text-[8px] font-black text-emerald-600 uppercase">Bolsa Total (22)</label>
                    <input type="number" step="0.5" name="vac_totales" id="edit_vac_totales" class="w-full bg-white border p-3 rounded-xl font-black text-emerald-700">
                </div>
                <div>
                    <label class="text-[8px] font-black text-emerald-600 uppercase">Disponibles</label>
                    <input type="number" step="0.5" name="vac_disponibles" id="edit_vac_disponibles" class="w-full bg-white border p-3 rounded-xl font-black text-emerald-700">
                </div>
            </div>

            <div>
                <label class="text-[9px] font-black text-rose-400 uppercase ml-2 italic">Resetear Password (Opcional)</label>
                <input type="password" name="password" placeholder="Solo para cambiarla..." class="w-full bg-white border p-4 rounded-2xl font-bold">
            </div>

            <button type="submit" class="w-full bg-amber-500 text-white font-black py-5 rounded-[30px] shadow-xl uppercase text-xs tracking-widest active:scale-95 transition">Actualizar Ficha</button>
        </form>
    </div>
</div>

<script>
function toggleModal(show) { document.getElementById('modalNuevo').classList.toggle('hidden', !show); }
function toggleModalEditar(show) { document.getElementById('modalEditar').classList.toggle('hidden', !show); }

function abrirModalEditar(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nombre').value = data.nombre;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_horario').value = data.horario;
    document.getElementById('edit_horas').value = data.horas_jornada;
    document.getElementById('edit_vac_totales').value = data.dias_vacaciones_totales;
    document.getElementById('edit_vac_disponibles').value = data.dias_vacaciones_disponibles;

    // Marcamos los días laborables
    const laborables = data.dias_laborables.split(',');
    for(let i=1; i<=7; i++) {
        document.getElementById('dia_'+i).checked = laborables.includes(i.toString());
    }
    toggleModalEditar(true);
}

document.getElementById('formEditarEmpleado').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('api/empleados_update.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => {
        if(data.success) location.reload();
        else Swal.fire('Error', data.message, 'error');
    });
}
</script>
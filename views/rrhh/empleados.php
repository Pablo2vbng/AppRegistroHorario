<?php
if ($_SESSION['rol'] != 'admin') exit();
$empleados = $pdo->query("SELECT * FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 px-2">
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Plantilla de Equipo</h1>
            <p class="text-slate-400 font-bold text-sm">Configuración de jornadas y horarios</p>
        </div>
        <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 text-white px-8 py-5 rounded-[30px] font-black shadow-2xl transition active:scale-95 uppercase text-xs">
            <i class="fas fa-plus-circle mr-2 text-lg"></i> Nuevo Trabajador
        </button>
    </header>

    <!-- VISTA MÓVIL: TARJETAS -->
    <div class="grid grid-cols-1 gap-4 md:hidden px-2">
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

    <!-- VISTA PC -->
    <div class="hidden md:block bg-white rounded-[50px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b text-[10px] font-black uppercase text-slate-400">
                <tr><th class="p-8">Empleado</th><th class="p-8">Contrato</th><th class="p-8 text-center">Vacaciones</th><th class="p-8 text-right">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($empleados as $emp): 
                    $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
                    $jsData = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-8 flex items-center">
                        <img src="<?php echo $foto; ?>" class="w-12 h-12 rounded-2xl object-cover mr-5 shadow-sm">
                        <span class="font-black text-slate-700 uppercase italic"><?php echo $emp['nombre']; ?></span>
                    </td>
                    <td class="p-8">
                        <p class="font-black text-slate-800"><?php echo $emp['horas_jornada']; ?> Horas / día</p>
                        <p class="text-[9px] text-slate-400 uppercase font-bold mt-1">Días: <?php echo str_replace(['1','2','3','4','5','6','7'],['L','M','X','J','V','S','D'],$emp['dias_laborables']); ?></p>
                    </td>
                    <td class="p-8 text-center"><span class="text-slate-800 font-black"><?php echo round($emp['dias_vacaciones_disponibles'], 1); ?></span><span class="text-slate-300 text-[10px]"> / 22</span></td>
                    <td class="p-8 text-right space-x-3">
                        <button onclick='abrirModalEditar(<?php echo $jsData; ?>)' class="text-amber-500 hover:text-amber-700 transition"><i class="fas fa-user-edit"></i></button>
                        <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="text-slate-400 hover:text-blue-600 transition"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR JORNADA Y DÍAS -->
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-xl overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-amber-500 p-8 text-white flex justify-between items-center">
            <h3 class="font-black uppercase tracking-widest text-[10px] italic">Personalizar Jornada Laboral</h3>
            <button onclick="toggleModalEditar(false)" class="text-2xl">&times;</button>
        </div>
        <form id="formEditarEmpleado" class="p-8 md:p-12 space-y-6 overflow-y-auto max-h-[85vh]">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                </div>
            </div>

            <div class="bg-slate-50 p-6 rounded-[35px] border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 text-center">Configuración de Contrato</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-[9px] font-black text-blue-600 uppercase ml-2">Horas por día</label>
                        <input type="number" step="0.5" name="horas_jornada" id="edit_horas" class="w-full bg-white border p-4 rounded-2xl font-black text-blue-700">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Tipo Horario</label>
                        <select name="horario" id="edit_horario" class="w-full bg-white border p-4 rounded-2xl font-bold">
                            <option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Partido">Partido</option><option value="Flexible">Flexible</option>
                        </select>
                    </div>
                </div>
                
                <label class="block text-[9px] font-black text-slate-400 uppercase mt-6 mb-3 ml-2">Días laborables de la semana</label>
                <div class="flex flex-wrap gap-2 justify-center">
                    <?php 
                    $diasArr = ['1'=>'L','2'=>'M','3'=>'X','4'=>'J','5'=>'V','6'=>'S','7'=>'D'];
                    foreach($diasArr as $val => $letra): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="dias[]" value="<?php echo $val; ?>" id="dia_<?php echo $val; ?>" class="hidden peer">
                            <div class="w-10 h-10 rounded-xl border-2 border-slate-200 flex items-center justify-center font-black text-xs text-slate-300 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all"><?php echo $letra; ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50/50 p-4 rounded-2xl">
                    <label class="text-[8px] font-black text-blue-400 uppercase">Vac. Totales</label>
                    <input type="number" step="0.5" name="vac_totales" id="edit_vac_totales" class="w-full bg-transparent font-black text-blue-600 outline-none">
                </div>
                <div class="bg-emerald-50/50 p-4 rounded-2xl">
                    <label class="text-[8px] font-black text-emerald-400 uppercase">Vac. Disponibles</label>
                    <input type="number" step="0.5" name="vac_disponibles" id="edit_vac_disponibles" class="w-full bg-transparent font-black text-emerald-600 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full bg-amber-500 text-white font-black py-5 rounded-[30px] shadow-xl uppercase text-xs tracking-widest transition active:scale-95">Actualizar Jornada</button>
        </form>
    </div>
</div>

<div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center font-black uppercase text-[10px]">Alta Nuevo Trabajador</div>
        <form id="formNuevoEmpleado" class="p-8 md:p-10 space-y-6">
            <input type="text" name="nombre" placeholder="Nombre completo" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="email" name="email" placeholder="Email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="toggleModal(false)" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl uppercase text-[10px]">Cerrar</button>
                <button type="submit" class="flex-[2] bg-blue-600 text-white font-black py-4 rounded-2xl shadow-xl uppercase text-[10px]">Guardar Alta</button>
            </div>
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

    // Resetear y marcar checkboxes de días
    const laborables = data.dias_laborables.split(',');
    for(let i=1; i<=7; i++) {
        document.getElementById('dia_'+i).checked = laborables.includes(i.toString());
    }
    toggleModalEditar(true);
}

document.getElementById('formNuevoEmpleado').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/empleados_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}

document.getElementById('formEditarEmpleado').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('api/empleados_update.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => {
        if(data.success) Swal.fire('Hecho', 'Configuración de jornada actualizada', 'success').then(() => location.reload());
        else Swal.fire('Error', data.message, 'error');
    });
}
</script>
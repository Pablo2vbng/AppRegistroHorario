<?php
if ($_SESSION['rol'] != 'admin') exit();
$empleados = $pdo->query("SELECT * FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 px-2">
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Plantilla Benigànim</h1>
            <p class="text-slate-400 font-bold text-sm">Gestión de accesos y perfiles de equipo</p>
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
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1"><?php echo $emp['horario']; ?></p>
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
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b text-[10px] font-black uppercase text-slate-400">
                <tr><th class="p-8">Empleado</th><th class="p-8">Horario</th><th class="p-8 text-center">Vacaciones</th><th class="p-8 text-right">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($empleados as $emp): 
                    $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
                    $jsData = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-8 flex items-center">
                        <img src="<?php echo $foto; ?>" class="w-12 h-12 rounded-2xl object-cover mr-5 shadow-sm">
                        <span class="font-black text-slate-700"><?php echo $emp['nombre']; ?></span>
                    </td>
                    <td class="p-8"><span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black uppercase text-slate-500 border"><?php echo $emp['horario']; ?></span></td>
                    <td class="p-8 text-center"><span class="text-slate-800 font-black"><?php echo round($emp['dias_vacaciones_disponibles'], 1); ?></span><span class="text-slate-300 text-[10px]"> / 22</span></td>
                    <td class="p-8 text-right space-x-3">
                        <button onclick='abrirModalEditar(<?php echo $jsData; ?>)' class="text-amber-500 hover:text-amber-700 transition" title="Editar datos"><i class="fas fa-user-edit"></i></button>
                        <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="text-slate-400 hover:text-blue-600 transition" title="Ver ficha"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR TRABAJADOR -->
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-amber-500 p-8 text-white flex justify-between items-center">
            <h3 class="font-black uppercase tracking-widest text-[10px] italic">Editar Perfil Trabajador</h3>
            <button onclick="toggleModalEditar(false)" class="text-2xl">&times;</button>
        </div>
        <form id="formEditarEmpleado" class="p-8 md:p-10 space-y-6 overflow-y-auto max-h-[70vh]">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-amber-500/10">
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-amber-500/10">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Horario</label>
                    <select name="horario" id="edit_horario" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none">
                        <option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Partido">Partido</option><option value="Flexible">Flexible</option>
                    </select>
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2 text-rose-500 italic">Nueva Password (opcional)</label>
                    <input type="password" name="password" placeholder="Solo para cambiarla" class="w-full bg-rose-50 border p-4 rounded-2xl font-bold outline-none focus:border-rose-400">
                </div>
            </div>
            <div class="bg-blue-50 p-6 rounded-3xl grid grid-cols-2 gap-6">
                <div>
                    <label class="text-[9px] font-black text-blue-600 uppercase ml-2">Días Totales Bolsa</label>
                    <input type="number" step="0.5" name="vac_totales" id="edit_vac_totales" class="w-full bg-white border p-4 rounded-2xl font-black text-blue-700">
                </div>
                <div>
                    <label class="text-[9px] font-black text-blue-600 uppercase ml-2">Días Disponibles Hoy</label>
                    <input type="number" step="0.5" name="vac_disponibles" id="edit_vac_disponibles" class="w-full bg-white border p-4 rounded-2xl font-black text-blue-700">
                </div>
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="toggleModalEditar(false)" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl uppercase text-[10px]">Cancelar</button>
                <button type="submit" class="flex-[2] bg-amber-500 text-white font-black py-4 rounded-2xl shadow-xl uppercase text-[10px]">Actualizar Trabajador</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ALTA (IGUAL QUE ANTES PERO LIMPIO) -->
<div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center font-black uppercase text-[10px]">Alta Nuevo Miembro</div>
        <form id="formNuevoEmpleado" class="p-8 md:p-10 space-y-6">
            <input type="text" name="nombre" placeholder="Nombre completo" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="email" name="email" placeholder="Email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <select name="horario" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold"><option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Partido">Partido</option></select>
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
    document.getElementById('edit_vac_totales').value = data.dias_vacaciones_totales;
    document.getElementById('edit_vac_disponibles').value = data.dias_vacaciones_disponibles;
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
        if(data.success) Swal.fire('Hecho', 'Perfil actualizado', 'success').then(() => location.reload());
        else Swal.fire('Error', data.message, 'error');
    });
}
</script>
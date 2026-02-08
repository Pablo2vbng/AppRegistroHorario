<?php
if ($_SESSION['rol'] != 'admin') exit();
$empleados = $pdo->query("SELECT * FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20 px-2">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">Gestión de Plantilla</h1>
            <p class="text-slate-400 font-bold text-sm mt-2 italic">Control total de perfiles y jornadas de CVTools</p>
        </div>
        <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 text-white px-8 py-4 rounded-[30px] font-black shadow-xl uppercase text-xs tracking-widest active:scale-95 transition">
            <i class="fas fa-user-plus mr-2"></i> Nuevo Empleado
        </button>
    </header>

    <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b text-[10px] font-black uppercase text-slate-400">
                    <tr><th class="p-8">Empleado</th><th class="p-8">Jornada</th><th class="p-8 text-center">Saldo Vac.</th><th class="p-8 text-right">Acciones</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50 font-bold text-sm">
                    <?php foreach($empleados as $emp): 
                        $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
                        $jsData = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="p-8 flex items-center">
                            <img src="<?php echo $foto; ?>" class="w-12 h-12 rounded-2xl object-cover mr-4 shadow-sm border-2 border-white">
                            <div><p class="font-black text-slate-800 uppercase italic leading-none"><?php echo $emp['nombre']; ?></p><p class="text-[10px] text-slate-400 mt-1 italic"><?php echo $emp['email']; ?></p></div>
                        </td>
                        <td class="p-8">
                            <p class="text-slate-700"><?php echo $emp['horas_jornada']; ?>h / día</p>
                            <span class="text-[9px] font-black uppercase bg-slate-100 px-2 py-1 rounded border"><?php echo $emp['horario']; ?></span>
                        </td>
                        <td class="p-8 text-center"><span class="text-blue-600 font-black text-lg"><?php echo round($emp['dias_vacaciones_disponibles'], 1); ?></span><span class="text-slate-300 text-xs"> / 22</span></td>
                        <td class="p-8 text-right space-x-2">
                            <button onclick='abrirModalEditar(<?php echo $jsData; ?>)' class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-500 hover:text-white transition"><i class="fas fa-user-edit"></i></button>
                            <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="inline-block w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg"><i class="fas fa-eye text-xs"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDITAR MAESTRO -->
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-xl overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-amber-500 p-8 text-white flex justify-between items-center"><h3 class="font-black uppercase tracking-widest text-[10px] italic">Editor de Trabajador</h3><button onclick="toggleModalEditar(false)" class="text-2xl">&times;</button></div>
        <form id="formEditarEmpleado" class="p-8 md:p-12 space-y-6 overflow-y-auto max-h-[80vh]">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nombre</label><input type="text" name="nombre" id="edit_nombre" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold"></div>
                <div><label class="text-[9px] font-black text-slate-400 uppercase ml-2">Email</label><input type="email" name="email" id="edit_email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold"></div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="text-[9px] font-black text-slate-400 uppercase ml-2">Horas/Día</label><input type="number" step="0.5" name="horas_jornada" id="edit_horas" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold"></div>
                <div><label class="text-[9px] font-black text-slate-400 uppercase ml-2">Saldo Disp.</label><input type="number" step="0.1" name="vac_disponibles" id="edit_vac_disp" class="w-full bg-blue-50 border-2 border-blue-100 p-4 rounded-2xl font-black text-blue-700"></div>
            </div>
            <div><label class="text-[9px] font-black text-rose-500 uppercase ml-2 italic">Resetear Contraseña (Opcional)</label><input type="password" name="password" placeholder="Nueva clave..." class="w-full bg-rose-50 border p-4 rounded-2xl font-bold"></div>
            <div class="flex gap-4">
                <button type="button" onclick="toggleModalEditar(false)" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl uppercase text-[10px]">Cerrar</button>
                <button type="submit" class="flex-[2] bg-amber-500 text-white font-black py-4 rounded-2xl shadow-xl uppercase text-[10px]">Guardar Todo</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModalEditar(show) { document.getElementById('modalEditar').classList.toggle('hidden', !show); }
function abrirModalEditar(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nombre').value = data.nombre;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_horas').value = data.horas_jornada;
    document.getElementById('edit_vac_disp').value = data.dias_vacaciones_disponibles;
    toggleModalEditar(true);
}
document.getElementById('formEditarEmpleado').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/empleados_update.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
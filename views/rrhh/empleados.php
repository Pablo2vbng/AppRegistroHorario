<?php
if ($_SESSION['rol'] != 'admin') exit();
$empleados = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Plantilla Benigànim</h1>
            <p class="text-slate-500 font-bold">Gestiona los perfiles y horarios de tu equipo</p>
        </div>
        <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-3xl font-black shadow-xl transition transform active:scale-95 flex items-center justify-center italic">
            <i class="fas fa-user-plus mr-3"></i> NUEVO EMPLEADO
        </button>
    </div>

    <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <tr>
                        <th class="p-6">Nombre y Apellidos</th>
                        <th class="p-6">Horario</th>
                        <th class="p-6">Vacaciones</th>
                        <th class="p-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($empleados as $emp): ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="p-6 flex items-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center font-black text-slate-400 mr-4 border-2 border-white shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <?php echo substr($emp['nombre'], 0, 1); ?>
                            </div>
                            <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="font-black text-slate-700 hover:text-blue-600 underline decoration-slate-200 underline-offset-4 decoration-2">
                                <?php echo $emp['nombre']; ?>
                            </a>
                        </td>
                        <td class="p-6">
                            <span class="px-3 py-1 bg-slate-100 rounded-lg text-[10px] font-bold text-slate-500 uppercase border border-slate-200">
                                <?php echo $emp['horario'] ?: 'Flexible'; ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="text-xs font-bold text-slate-400">
                                <span class="text-slate-800 font-black text-sm"><?php echo $emp['dias_vacaciones_disponibles']; ?></span> / 22 días
                            </div>
                        </td>
                        <td class="p-6 text-right">
                            <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="inline-block bg-slate-100 text-slate-600 p-3 rounded-xl hover:bg-slate-900 hover:text-white transition">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-[40px] shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center font-black uppercase text-xs">Añadir Miembro</div>
        <form id="formNuevoEmpleado" class="p-10 space-y-6">
            <input type="text" name="nombre" placeholder="Nombre completo" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="email" name="email" placeholder="Email" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            
            <div class="grid grid-cols-2 gap-4">
                <select name="rol" class="bg-slate-50 border p-4 rounded-2xl font-bold">
                    <option value="empleado">Empleado</option>
                    <option value="admin">Administrador</option>
                </select>
                <select name="horario" class="bg-slate-50 border p-4 rounded-2xl font-bold text-blue-600">
                    <option value="Mañana">Mañana</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Partido">Partido</option>
                    <option value="Flexible">Flexible</option>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-black py-5 rounded-3xl shadow-xl uppercase text-xs">Crear Cuenta</button>
        </form>
    </div>
</div>

<script>
function toggleModal(show) { document.getElementById('modalNuevo').classList.toggle('hidden', !show); }
document.getElementById('formNuevoEmpleado').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/empleados_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
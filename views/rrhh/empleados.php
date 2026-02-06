<?php
if ($_SESSION['rol'] != 'admin') exit();
$empleados = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic">Gestión de Plantilla</h1>
        <button onclick="toggleModal(true)" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase shadow-xl">Añadir Empleado</button>
    </div>

    <div class="bg-white rounded-[40px] shadow-sm border overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400">
                <tr>
                    <th class="p-6">Nombre</th>
                    <th class="p-6">Email</th>
                    <th class="p-6">Horario Asignado</th>
                    <th class="p-6 text-right">Rol</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach($empleados as $e): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-black text-slate-700"><?php echo $e['nombre']; ?></td>
                    <td class="p-6 text-slate-400 text-sm"><?php echo $e['email']; ?></td>
                    <td class="p-6">
                        <span class="bg-slate-100 px-3 py-1 rounded-lg text-[10px] font-black uppercase text-slate-600 border">
                            <i class="far fa-clock mr-1"></i> <?php echo $e['horario'] ?: 'Flexible'; ?>
                        </span>
                    </td>
                    <td class="p-6 text-right">
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-lg <?php echo $e['rol'] == 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                            <?php echo $e['rol']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal actualizado con campo Horario -->
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
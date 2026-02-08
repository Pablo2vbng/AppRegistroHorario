<?php
if ($_SESSION['rol'] != 'admin') exit();
// Solo listamos trabajadores reales
$empleados = $pdo->query("SELECT * FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-20">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 px-2">
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Plantilla de Equipo</h1>
            <p class="text-slate-400 font-bold text-sm">Gestiona altas y perfiles de Benigànim</p>
        </div>
        <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 text-white px-8 py-5 rounded-[30px] font-black shadow-2xl transition active:scale-95 uppercase text-xs tracking-widest">
            <i class="fas fa-plus-circle mr-2 text-lg"></i> Nuevo Trabajador
        </button>
    </header>

    <!-- VISTA MÓVIL: TARJETAS (CARDS) -->
    <div class="grid grid-cols-1 gap-4 md:hidden px-2">
        <?php foreach($empleados as $emp): 
            $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
        ?>
        <div class="bg-white p-6 rounded-[40px] shadow-sm border border-slate-200 flex items-center justify-between group">
            <div class="flex items-center gap-4">
                <img src="<?php echo $foto; ?>" class="w-14 h-14 rounded-[20px] object-cover shadow-md">
                <div>
                    <p class="font-black text-slate-800 uppercase text-xs"><?php echo $emp['nombre']; ?></p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1"><?php echo $emp['horario'] ?: 'Flexible'; ?></p>
                </div>
            </div>
            <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-eye text-sm"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- VISTA PC: TABLA CLÁSICA -->
    <div class="hidden md:block bg-white rounded-[50px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b text-[10px] font-black uppercase text-slate-400">
                <tr>
                    <th class="p-8">Empleado</th>
                    <th class="p-8">Horario</th>
                    <th class="p-8">Vacaciones</th>
                    <th class="p-8 text-right">Ficha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($empleados as $emp): 
                    $foto = $emp['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($emp['nombre']);
                ?>
                <tr class="hover:bg-slate-50 transition group">
                    <td class="p-8 flex items-center">
                        <img src="<?php echo $foto; ?>" class="w-12 h-12 rounded-2xl object-cover mr-5 shadow-sm">
                        <span class="font-black text-slate-700 italic"><?php echo $emp['nombre']; ?></span>
                    </td>
                    <td class="p-8">
                        <span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black uppercase text-slate-500 border border-slate-200"><?php echo $emp['horario'] ?: 'Flexible'; ?></span>
                    </td>
                    <td class="p-8">
                        <span class="text-slate-800 font-black text-sm"><?php echo round($emp['dias_vacaciones_disponibles'], 1); ?></span> <span class="text-slate-300 text-[10px]">/ 22</span>
                    </td>
                    <td class="p-8 text-right">
                        <a href="index.php?p=empleado_detalle&id=<?php echo $emp['id']; ?>" class="inline-block bg-slate-100 text-slate-400 p-4 rounded-2xl hover:bg-slate-900 hover:text-white transition shadow-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ALTA (Responsivo) -->
<div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center">
            <h3 class="font-black uppercase tracking-widest text-[10px] italic">Alta Nuevo Trabajador</h3>
            <button onclick="toggleModal(false)" class="text-2xl text-slate-400">&times;</button>
        </div>
        <form id="formNuevoEmpleado" class="p-8 md:p-10 space-y-6">
            <input type="text" name="nombre" placeholder="Nombre completo" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-500/10">
            <input type="email" name="email" placeholder="Email corporativo" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-500/10">
            <input type="password" name="password" placeholder="Contraseña inicial" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-500/10">
            <select name="horario" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none">
                <option value="Mañana">Mañana</option>
                <option value="Tarde">Tarde</option>
                <option value="Partido">Partido</option>
            </select>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="toggleModal(false)" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl uppercase text-[10px]">Cerrar</button>
                <button type="submit" class="flex-[2] bg-blue-600 text-white font-black py-4 rounded-2xl shadow-xl uppercase text-[10px]">Guardar Alta</button>
            </div>
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
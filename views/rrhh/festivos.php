<?php
if ($_SESSION['rol'] != 'admin') exit();
$festivos = $pdo->query("SELECT * FROM festivos ORDER BY fecha ASC")->fetchAll();
?>

<div class="max-w-5xl mx-auto pb-20 px-2">
    <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Festivos Benigànim</h1>
            <p class="text-slate-400 font-bold text-sm">Añade o quita días laborables del calendario</p>
        </div>
        <button onclick="toggleModalFestivo(true)" class="w-full md:w-auto bg-emerald-500 text-white px-8 py-4 rounded-[30px] font-black shadow-xl uppercase text-xs tracking-widest active:scale-95 transition">
            <i class="fas fa-calendar-plus mr-2"></i> Nuevo Festivo
        </button>
    </header>

    <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 border-b">
                    <tr>
                        <th class="p-8">Fecha</th>
                        <th class="p-8">Festividad</th>
                        <th class="p-8">Tipo</th>
                        <th class="p-8">Impacto</th>
                        <th class="p-8 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 font-bold text-sm">
                    <?php foreach($festivos as $f): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-8 text-slate-500 italic"><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
                        <td class="p-8 font-black uppercase text-slate-800"><?php echo $f['nombre']; ?></td>
                        <td class="p-8">
                            <span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black uppercase border"><?php echo $f['tipo']; ?></span>
                        </td>
                        <td class="p-8">
                            <?php if($f['descuenta_vacaciones']): ?>
                                <span class="text-rose-500 text-[9px] font-black uppercase italic italic tracking-tighter">Cierre Empresa (-1 d)</span>
                            <?php else: ?>
                                <span class="text-emerald-500 text-[9px] font-black uppercase italic tracking-tighter">Gratis</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-8 text-right">
                            <button onclick="eliminarFestivo(<?php echo $f['id']; ?>)" class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL NUEVO FESTIVO -->
<div id="modalFestivo" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[150] flex items-center justify-center p-4">
    <div class="bg-white rounded-[45px] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in duration-300">
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center">
            <h3 class="font-black uppercase tracking-widest text-[10px] italic">Añadir día al calendario</h3>
            <button onclick="toggleModalFestivo(false)" class="text-2xl">&times;</button>
        </div>
        <form id="formNuevoFestivo" class="p-10 space-y-6">
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Fecha del festivo</label>
                <input type="date" name="fecha" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold mt-2">
            </div>
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nombre del evento</label>
                <input type="text" name="nombre" placeholder="Ej: San Canuto Local" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold mt-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <select name="tipo" class="bg-slate-50 border p-4 rounded-2xl font-bold uppercase text-xs">
                    <option value="local">Local</option><option value="nacional">Nacional</option><option value="comunidad">Comunidad</option>
                </select>
                <div class="flex items-center justify-center gap-2 bg-rose-50 rounded-2xl border border-rose-100 p-2">
                    <input type="checkbox" name="descuenta" id="desc_check" class="w-4 h-4">
                    <label for="desc_check" class="text-[8px] font-black text-rose-700 uppercase">Cierre Empresa</label>
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-500 text-white font-black py-5 rounded-[30px] shadow-xl uppercase text-xs tracking-widest active:scale-95 transition">Guardar Festivo</button>
        </form>
    </div>
</div>

<script>
function toggleModalFestivo(show) { document.getElementById('modalFestivo').classList.toggle('hidden', !show); }

document.getElementById('formNuevoFestivo').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/festivos_crear.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json()).then(data => data.success ? location.reload() : Swal.fire('Error', data.message, 'error'));
}

function eliminarFestivo(id) {
    Swal.fire({
        title: '¿Eliminar festivo?',
        text: "Si era cierre de empresa, se devolverá el día a los trabajadores.",
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'ELIMINAR'
    }).then((r) => {
        if(r.isConfirmed) fetch('api/festivos_eliminar.php?id='+id).then(res => res.json()).then(data => location.reload());
    });
}
</script>
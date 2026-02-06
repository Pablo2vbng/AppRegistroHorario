<?php
// 1. Obtener lista de empleados
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll();

$userId = $_GET['user_id'] ?? '';
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

$jornadas = [];
$nombre_empleado = "";

if ($userId) {
    $stmtU = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtU->execute([$userId]);
    $nombre_empleado = $stmtU->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fichajes as $f) {
        $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
        $jornadas[$fecha][] = $f;
    }
}
?>

<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Supervisión de Jornadas</h1>
            <p class="text-slate-400 text-sm font-bold"><?php echo $nombre_empleado ?: 'Selecciona un empleado para revisar'; ?></p>
        </div>
        <?php if($userId): ?>
        <button onclick="abrirModalAjuste()" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-xl font-black text-xs uppercase shadow-lg transition">
            <i class="fas fa-plus-circle mr-2"></i> Añadir Fichaje Manual
        </button>
        <?php endif; ?>
    </div>

    <!-- FILTROS -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border mb-8 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="p" value="informes_equipo">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Empleado</label>
                <select name="user_id" class="w-full bg-slate-50 border p-3 rounded-xl font-bold text-slate-700" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Mes</label>
                <select name="mes" class="w-full bg-slate-50 border p-3 rounded-xl font-bold text-slate-700">
                    <?php for($m=1;$m<=12;$m++): $m2=str_pad($m,2,'0',STR_PAD_LEFT); ?>
                        <option value="<?php echo $m2; ?>" <?php echo ($mes==$m2)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Año</label>
                <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-3 rounded-xl font-bold text-slate-700">
            </div>
            <button type="submit" class="bg-slate-900 text-white p-4 rounded-xl font-black uppercase text-[10px] tracking-widest">Filtrar</button>
        </form>
    </div>

    <!-- TABLA DE REGISTROS -->
    <div class="bg-white rounded-3xl shadow-sm border overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b">
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-5">Fecha</th>
                    <th class="p-5">Eventos del día</th>
                    <th class="p-5 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach(array_reverse($jornadas) as $fecha => $eventos): 
                    $totalSec = 0;
                    if(count($eventos) >= 2) {
                        $entrada = $eventos[0]['fecha_hora'];
                        $salida = end($eventos);
                        if($salida['tipo'] == 'salida') {
                            $totalSec = strtotime($salida['fecha_hora']) - strtotime($entrada);
                        }
                    }
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-5 font-black text-slate-700 w-40"><?php echo date('d M Y', strtotime($fecha)); ?></td>
                    <td class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($eventos as $ev): ?>
                                <span class="px-3 py-1 bg-slate-100 rounded-lg text-[10px] font-bold text-slate-600 border border-slate-200">
                                    <i class="far fa-clock mr-1"></i> <?php echo date('H:i', strtotime($ev['fecha_hora'])); ?> 
                                    <span class="ml-1 uppercase opacity-50"><?php echo $ev['tipo']; ?></span>
                                    <?php if($ev['modificado_por']): ?>
                                        <i class="fas fa-edit ml-1 text-amber-500" title="Editado manualmente"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="p-5 text-right font-black text-slate-800">
                        <?php echo floor($totalSec/3600); ?>h <?php echo floor(($totalSec/60)%60); ?>m
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AJUSTE MANUAL -->
<div id="modalAjuste" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in duration-200">
        <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
            <h3 class="font-black uppercase text-xs tracking-widest italic">Ajuste de Fichaje Manual</h3>
            <button onclick="abrirModalAjuste(false)" class="text-xl">&times;</button>
        </div>
        <form id="formAjuste" class="p-8 space-y-5">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Fecha y Hora</label>
                <input type="datetime-local" name="fecha_hora" required class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Tipo de Registro</label>
                <select name="tipo" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold">
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="pausa">Pausa</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Motivo del ajuste (Obligatorio Legal)</label>
                <textarea name="notas" required class="w-full bg-slate-50 border p-4 rounded-2xl text-sm" placeholder="Ej: Olvido de fichaje al salir..."></textarea>
            </div>
            <button type="submit" class="w-full bg-emerald-500 text-white font-black py-4 rounded-2xl shadow-lg uppercase text-xs tracking-widest">Guardar Ajuste</button>
        </form>
    </div>
</div>

<script>
function abrirModalAjuste(show = true) {
    document.getElementById('modalAjuste').classList.toggle('hidden', !show);
}

document.getElementById('formAjuste').onsubmit = function(e) {
    e.preventDefault();
    fetch('api/fichaje_manual.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => data.success ? location.reload() : alert(data.message));
}
</script>
<?php
if ($_SESSION['rol'] != 'admin') exit();
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'admin' ORDER BY nombre ASC")->fetchAll();
$userId = $_GET['user_id'] ?? '';
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

$jornadas = [];
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fichajes as $f) { $fecha = date('Y-m-d', strtotime($f['fecha_hora'])); $jornadas[$fecha][] = $f; }
}
?>

<div class="max-w-6xl mx-auto pb-20">
    <div class="flex justify-between items-center mb-10 px-2 no-print">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic">Auditoría GPS</h1>
        <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Imprimir</button>
    </div>

    <div class="bg-white p-6 rounded-[35px] border border-slate-200 mb-8 no-print shadow-sm mx-2">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="p" value="informes_equipo">
            <select name="user_id" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs" required>
                <option value="">-- Empleado --</option>
                <?php foreach($usuarios as $u): ?><option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option><?php endforeach; ?>
            </select>
            <select name="mes" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
                <?php for($m=1;$m<=12;$m++): $m2=str_pad($m,2,'0',STR_PAD_LEFT); ?><option value="<?php echo $m2; ?>" <?php echo ($mes==$m2)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option><?php endfor; ?>
            </select>
            <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
            <button type="submit" class="bg-blue-600 text-white p-4 rounded-2xl font-black uppercase text-[10px]">Filtrar</button>
        </form>
    </div>

    <div class="space-y-4 mx-2">
        <?php foreach(array_reverse($jornadas) as $fecha => $eventos): ?>
            <div class="bg-white rounded-[35px] border border-slate-200 p-8 shadow-sm">
                <p class="text-xs font-black text-slate-800 uppercase mb-6 italic"><?php echo date('l, d M Y', strtotime($fecha)); ?></p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php foreach($eventos as $ev): 
                        $statusClass = $ev['fuera_rango'] ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-100';
                        $iconColor = $ev['fuera_rango'] ? 'text-rose-500' : 'text-emerald-500';
                    ?>
                        <div class="flex items-center justify-between p-4 rounded-2xl border <?php echo $statusClass; ?>">
                            <div>
                                <p class="text-[8px] font-black text-slate-400 uppercase mb-1"><?php echo $ev['tipo']; ?></p>
                                <p class="font-black text-slate-700 text-sm"><?php echo date('H:i', strtotime($ev['fecha_hora'])); ?></p>
                                <?php if($ev['fuera_rango']): ?>
                                    <span class="text-[7px] font-black text-rose-600 bg-rose-100 px-1 rounded uppercase">Fuera de CVTools</span>
                                <?php endif; ?>
                            </div>
                            <?php if($ev['latitud']): ?>
                                <a href="https://www.google.com/maps?q=<?php echo $ev['latitud']; ?>,<?php echo $ev['longitud']; ?>" target="_blank" class="<?php echo $iconColor; ?> hover:scale-110 transition">
                                    <i class="fas fa-map-marked-alt text-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
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
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 px-2">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">Control Horario & GPS</h1>
            <p class="text-slate-400 font-bold text-sm mt-2">Supervisión de ubicación y tiempos de Benigànim</p>
        </div>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl">Imprimir</button>
    </div>

    <!-- FILTROS -->
    <div class="bg-white p-8 rounded-[40px] border border-slate-200 mb-8 no-print mx-2 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <input type="hidden" name="p" value="informes_equipo">
            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2">Empleado</label>
                <select name="user_id" class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-2xl font-bold text-sm outline-none focus:border-blue-500" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach($usuarios as $u): ?><option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2">Mes</label>
                <select name="mes" class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-2xl font-bold text-sm">
                    <?php for($m=1;$m<=12;$m++): $m2=str_pad($m,2,'0',STR_PAD_LEFT); ?><option value="<?php echo $m2; ?>" <?php echo ($mes==$m2)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option><?php endfor; ?>
                </select>
            </div>
            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2">Año</label>
                <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-2xl font-bold text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white p-5 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-lg shadow-blue-100 hover:bg-blue-700 transition active:scale-95">Filtrar</button>
        </form>
    </div>

    <?php if($userId): ?>
    <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 overflow-hidden mx-2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b">
                    <tr>
                        <th class="p-8">Fecha</th>
                        <th class="p-8">Registros con Geolocalización</th>
                        <th class="p-8 text-right">Tiempo Efectivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach(array_reverse($jornadas) as $fecha => $eventos): 
                        $totalS = 0; $start = null;
                        foreach($eventos as $ev) {
                            if($ev['tipo']=='entrada' || $ev['tipo']=='reanudar') $start = strtotime($ev['fecha_hora']);
                            if(($ev['tipo']=='pausa' || $ev['tipo']=='salida') && $start) { $totalS += (strtotime($ev['fecha_hora']) - $start); $start = null; }
                        }
                    ?>
                    <tr class="hover:bg-slate-50/50 transition duration-300">
                        <td class="p-8 font-black text-slate-700 italic"><?php echo date('d M Y', strtotime($fecha)); ?></td>
                        <td class="p-8">
                            <div class="flex flex-wrap gap-3">
                                <?php foreach($eventos as $ev): ?>
                                    <div class="flex items-center bg-white border border-slate-100 pr-4 rounded-2xl shadow-sm overflow-hidden">
                                        <div class="bg-slate-900 text-white text-[9px] font-black px-3 py-2 uppercase mr-3">
                                            <?php echo date('H:i', strtotime($ev['fecha_hora'])); ?>
                                        </div>
                                        <span class="text-[9px] font-black uppercase text-slate-400 mr-4 tracking-tighter"><?php echo $ev['tipo']; ?></span>
                                        <?php if($ev['latitud']): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $ev['latitud']; ?>,<?php echo $ev['longitud']; ?>" target="_blank" 
                                               class="w-8 h-8 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-inner">
                                                <i class="fas fa-map-marked-alt text-[10px]"></i>
                                            </a>
                                        <?php else: ?>
                                            <i class="fas fa-map-marker-slash text-slate-200 text-xs" title="Sin GPS"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="p-8 text-right">
                            <span class="text-xl font-black text-slate-800 tracking-tighter italic"><?php echo floor($totalS/3600); ?>h <?php echo floor(($totalS/60)%60); ?>m</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="text-center py-20 bg-white rounded-[50px] border-2 border-dashed border-slate-200 mx-2">
            <i class="fas fa-user-search text-slate-100 text-8xl mb-6"></i>
            <p class="text-slate-400 font-black uppercase italic tracking-widest text-xs">Selecciona un empleado para auditar sus ubicaciones</p>
        </div>
    <?php endif; ?>
</div>
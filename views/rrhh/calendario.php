<?php
// Obtener mes y año actual o del filtro
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Obtener ausencias aprobadas para este mes
$sql = "SELECT a.*, u.nombre as empleado 
        FROM ausencias a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.estado = 'aprobado' 
        AND ((MONTH(a.fecha_inicio) = ? AND YEAR(a.fecha_inicio) = ?) 
        OR (MONTH(a.fecha_fin) = ? AND YEAR(a.fecha_fin) = ?))";
$stmt = $pdo->prepare($sql);
$stmt->execute([$mes, $anio, $mes, $anio]);
$ausencias = $stmt->fetchAll();

// Lógica de construcción de días del calendario
$primerDia = date('N', strtotime("$anio-$mes-01"));
$diasMes = date('t', strtotime("$anio-$mes-01"));
?>

<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic">Calendario de Equipo</h1>
        
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="p" value="calendario_equipo">
            <select name="mes" class="bg-white border p-2 rounded-lg font-bold text-sm">
                <?php for($i=1; $i<=12; $i++): ?>
                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($mes == $i) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase">Ver</button>
        </form>
    </div>

    <!-- CALENDARIO VISUAL -->
    <div class="bg-white rounded-3xl shadow-sm border p-6 overflow-hidden">
        <div class="grid grid-cols-7 gap-px bg-slate-200 border border-slate-200 rounded-xl overflow-hidden text-center">
            <!-- Cabecera días -->
            <?php foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
                <div class="bg-slate-50 p-3 text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo $d; ?></div>
            <?php endforeach; ?>

            <!-- Huecos iniciales -->
            <?php for($i=1; $i<$primerDia; $i++): ?>
                <div class="bg-white p-4 h-32"></div>
            <?php endfor; ?>

            <!-- Días del mes -->
            <?php for($dia=1; $dia<=$diasMes; $dia++): 
                $fechaActual = date('Y-m-d', strtotime("$anio-$mes-$dia"));
                $hayAusencia = array_filter($ausencias, function($a) use ($fechaActual) {
                    return $fechaActual >= $a['fecha_inicio'] && $fechaActual <= $a['fecha_fin'];
                });
            ?>
                <div class="bg-white p-2 h-32 border-slate-50 relative group hover:bg-slate-50 transition">
                    <span class="text-xs font-black text-slate-300"><?php echo $dia; ?></span>
                    <div class="mt-1 space-y-1">
                        <?php foreach($hayAusencia as $aus): 
                            $color = ($aus['tipo'] == 'vacaciones') ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
                        ?>
                            <div class="text-[9px] font-bold px-2 py-0.5 rounded-md truncate <?php echo $color; ?>" title="<?php echo $aus['empleado']; ?>">
                                <?php echo explode(' ', $aus['empleado'])[0]; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <div class="mt-6 flex gap-4 text-xs font-bold text-slate-400 italic">
        <div class="flex items-center"><span class="w-3 h-3 bg-emerald-200 rounded mr-2"></span> Vacaciones</div>
        <div class="flex items-center"><span class="w-3 h-3 bg-rose-200 rounded mr-2"></span> Bajas / Otros</div>
    </div>
</div>
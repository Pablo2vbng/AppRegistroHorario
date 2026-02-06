<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['usuario_id'];

// 1. Obtener Festivos y Cierres - Formateamos para búsqueda rápida
$stmtF = $pdo->prepare("SELECT fecha, nombre, tipo, descuenta_vacaciones FROM festivos WHERE YEAR(fecha) = ?");
$stmtF->execute([$anio]);
$festivosRaw = $stmtF->fetchAll(PDO::FETCH_ASSOC);
$festivos = [];
foreach($festivosRaw as $f) { $festivos[$f['fecha']] = $f; }

// 2. Obtener Ausencias Aprobadas (Si es 'all' para Admin o el ID del empleado)
$sqlA = "SELECT a.*, u.nombre as emp_nombre FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND (YEAR(a.fecha_inicio) = ? OR YEAR(a.fecha_fin) = ?)";
if ($_SESSION['rol'] == 'admin' && $userId !== 'all') {
    $sqlA .= " AND a.usuario_id = " . intval($userId);
} elseif ($_SESSION['rol'] != 'admin') {
    $sqlA .= " AND a.usuario_id = " . intval($_SESSION['usuario_id']);
}
$stmtA = $pdo->prepare($sqlA);
$stmtA->execute([$anio, $anio]);
$ausencias = $stmtA->fetchAll();

// 3. Lista empleados para filtro Admin
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'admin' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Calendario Laboral <?php echo $anio; ?></h1>
            <p class="text-slate-400 font-bold text-sm">Visualización de festivos y ausencias • Benigànim</p>
        </div>
        
        <?php if($_SESSION['rol'] == 'admin'): ?>
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="p" value="calendario_anual">
            <select name="user_id" onchange="this.form.submit()" class="bg-white border-2 border-slate-100 p-3 rounded-2xl font-bold text-xs uppercase shadow-sm outline-none focus:border-blue-500">
                <option value="all" <?php echo ($userId == 'all')?'selected':''; ?>>✨ EQUIPO COMPLETO</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <!-- REJILLA DE MESES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php for($m=1; $m<=12; $m++): 
            $nombreMes = date('F', mktime(0, 0, 0, $m, 1));
            $primerDia = date('N', strtotime("$anio-$m-01"));
            $diasMes = date('t', strtotime("$anio-$m-01"));
        ?>
        <div class="bg-white rounded-[35px] shadow-sm border border-slate-100 p-6">
            <h3 class="text-center font-black uppercase italic text-slate-800 mb-6 tracking-widest text-[10px] border-b border-slate-50 pb-3"><?php echo $nombreMes; ?></h3>
            
            <div class="grid grid-cols-7 gap-1 text-[8px] text-center font-black text-slate-300 mb-3 uppercase tracking-tighter">
                <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <?php for($i=1; $i<$primerDia; $i++) echo '<div></div>'; ?>
                
                <?php for($dia=1; $dia<=$diasMes; $dia++): 
                    $fechaC = sprintf("%s-%02d-%02d", $anio, $m, $dia);
                    $clase = "bg-slate-50 text-slate-300";
                    $label = "";
                    $listaNombres = [];

                    // 1. FESTIVOS
                    if(isset($festivos[$fechaC])) {
                        $f = $festivos[$fechaC];
                        if($f['descuenta_vacaciones']) {
                            $clase = "bg-rose-500 text-white shadow-lg shadow-rose-100"; // CIERRE EMPRESA
                        } else {
                            $clase = ($f['tipo'] == 'local') ? "bg-amber-400 text-white" : "bg-blue-600 text-white"; // FESTIVO
                        }
                        $label = $f['nombre'];
                    }

                    // 2. AUSENCIAS
                    foreach($ausencias as $aus) {
                        if($fechaC >= $aus['fecha_inicio'] && $fechaC <= $aus['fecha_fin']) {
                            $clase = "bg-emerald-500 text-white shadow-lg shadow-emerald-100"; // VACACIONES
                            $listaNombres[] = explode(' ', $aus['emp_nombre'])[0];
                        }
                    }
                ?>
                    <div title="<?php echo $label; ?>" class="aspect-square flex flex-col items-center justify-center rounded-xl text-[10px] font-black transition-all hover:scale-125 relative group <?php echo $clase; ?>">
                        <span><?php echo $dia; ?></span>
                        <?php if($userId == 'all' && !empty($listaNombres)): ?>
                            <div class="hidden group-hover:block absolute bottom-full mb-1 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white p-2 rounded-lg text-[8px] whitespace-nowrap shadow-2xl">
                                <?php echo implode(', ', array_unique($listaNombres)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- LEYENDA -->
    <div class="mt-16 flex flex-wrap justify-center gap-8 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 bg-white p-8 rounded-[35px] border border-slate-100 shadow-sm">
        <div class="flex items-center"><span class="w-4 h-4 bg-blue-600 rounded-lg mr-2"></span> Nacional / Comunidad</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-amber-400 rounded-lg mr-2"></span> Local Benigànim</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-rose-500 rounded-lg mr-2"></span> Cierre CVTools</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-emerald-500 rounded-lg mr-2"></span> Vacaciones Aprobadas</div>
    </div>
</div>
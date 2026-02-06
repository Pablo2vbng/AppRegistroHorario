<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['usuario_id'];

// 1. Obtener Festivos
$stmtF = $pdo->prepare("SELECT fecha, nombre, tipo, descuenta_vacaciones FROM festivos WHERE YEAR(fecha) = ?");
$stmtF->execute([$anio]);
$festivosRaw = $stmtF->fetchAll(PDO::FETCH_ASSOC);
$festivos = [];
foreach($festivosRaw as $f) { $festivos[$f['fecha']] = $f; }

// 2. Obtener Ausencias (Si es 'all' cargamos todas, si no, solo el ID seleccionado)
$sqlA = "SELECT a.*, u.nombre as emp_nombre FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND (YEAR(a.fecha_inicio) = ? OR YEAR(a.fecha_fin) = ?)";
if ($_SESSION['rol'] == 'admin' && $userId !== 'all') {
    $sqlA .= " AND a.usuario_id = " . intval($userId);
} elseif ($_SESSION['rol'] != 'admin') {
    $sqlA .= " AND a.usuario_id = " . intval($_SESSION['usuario_id']);
}

$stmtA = $pdo->prepare($sqlA);
$stmtA->execute([$anio, $anio]);
$ausencias = $stmtA->fetchAll();

// 3. Lista empleados para Admin
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Planificador CVTools <?php echo $anio; ?></h1>
            <p class="text-slate-400 font-bold text-sm">Calendario de festivos y ausencias de equipo</p>
        </div>
        
        <?php if($_SESSION['rol'] == 'admin'): ?>
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="p" value="calendario_anual">
            <select name="user_id" onchange="this.form.submit()" class="bg-white border-2 border-slate-100 p-3 rounded-2xl font-bold text-xs uppercase shadow-sm outline-none">
                <option value="all" <?php echo ($userId == 'all')?'selected':''; ?>>✨ TODOS LOS TRABAJADORES</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php for($m=1; $m<=12; $m++): 
            $nombreMes = date('F', mktime(0, 0, 0, $m, 1));
            $primerDia = date('N', strtotime("$anio-$m-01"));
            $diasMes = date('t', strtotime("$anio-$m-01"));
        ?>
        <div class="bg-white rounded-[35px] shadow-sm border border-slate-100 p-4 relative overflow-hidden">
            <h3 class="text-center font-black uppercase text-slate-800 mb-4 text-[10px] tracking-widest"><?php echo $nombreMes; ?></h3>
            <div class="grid grid-cols-7 gap-1">
                <?php for($i=1; $i<$primerDia; $i++) echo '<div></div>'; ?>
                <?php for($dia=1; $dia<=$diasMes; $dia++): 
                    $fechaC = sprintf("%s-%02d-%02d", $anio, $m, $dia);
                    $claseBase = "bg-slate-50 text-slate-300";
                    $labelFestivo = "";
                    $listaNombres = [];

                    // Comprobar Festivos
                    if(isset($festivos[$fechaC])) {
                        $f = $festivos[$fechaC];
                        $claseBase = $f['descuenta_vacaciones'] ? "bg-rose-500 text-white" : (($f['tipo'] == 'local') ? "bg-amber-400 text-white" : "bg-blue-600 text-white");
                        $labelFestivo = $f['nombre'];
                    }

                    // Comprobar Ausencias
                    foreach($ausencias as $aus) {
                        if($fechaC >= $aus['fecha_inicio'] && $fechaC <= $aus['fecha_fin']) {
                            if($userId == 'all') {
                                $listaNombres[] = explode(' ', $aus['emp_nombre'])[0];
                                $claseBase = "bg-emerald-500 text-white"; // Color vacaciones si hay alguien
                            } else {
                                $claseBase = ($aus['tipo'] == 'vacaciones') ? "bg-emerald-500 text-white" : "bg-rose-500 text-white";
                            }
                        }
                    }
                ?>
                    <div class="aspect-square flex flex-col items-center justify-center rounded-lg text-[9px] font-black relative group <?php echo $claseBase; ?>" title="<?php echo $labelFestivo; ?>">
                        <span><?php echo $dia; ?></span>
                        <?php if($userId == 'all' && !empty($listaNombres)): ?>
                            <div class="hidden group-hover:block absolute top-full left-0 z-20 bg-slate-800 text-white p-2 rounded shadow-xl text-[8px] whitespace-nowrap">
                                <?php echo implode(', ', $listaNombres); ?>
                            </div>
                            <div class="flex gap-0.5 mt-0.5">
                                <?php foreach(array_slice($listaNombres, 0, 3) as $n) echo '<span class="w-1 h-1 bg-white rounded-full"></span>'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <div class="mt-12 p-6 bg-white rounded-3xl border border-slate-100 flex flex-wrap justify-center gap-6 text-[9px] font-black uppercase text-slate-400">
        <div class="flex items-center"><span class="w-3 h-3 bg-blue-600 rounded mr-2"></span> Nacional</div>
        <div class="flex items-center"><span class="w-3 h-3 bg-amber-400 rounded mr-2"></span> Local Benigànim</div>
        <div class="flex items-center"><span class="w-3 h-3 bg-rose-500 rounded mr-2"></span> Cierre Empresa</div>
        <div class="flex items-center"><span class="w-3 h-3 bg-emerald-500 rounded mr-2"></span> Vacaciones Aprobadas</div>
    </div>
</div>
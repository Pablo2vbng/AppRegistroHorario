<?php
$anioBase = 2026;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['usuario_id'];

// 1. Obtener Festivos y Cierres (2026 y 2027)
$stmtF = $pdo->prepare("SELECT fecha, nombre, tipo, descuenta_vacaciones FROM festivos WHERE YEAR(fecha) IN (?, ?)");
$stmtF->execute([$anioBase, $anioBase + 1]);
$festivosRaw = $stmtF->fetchAll(PDO::FETCH_ASSOC);
$festivos = [];
foreach($festivosRaw as $f) { $festivos[$f['fecha']] = $f; }

// 2. Obtener Ausencias Aprobadas
$sqlA = "SELECT a.*, u.nombre as emp_nombre FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND (YEAR(a.fecha_inicio) IN (?, ?) OR YEAR(a.fecha_fin) IN (?, ?))";
if ($_SESSION['rol'] == 'admin' && $userId !== 'all') {
    $sqlA .= " AND a.usuario_id = " . intval($userId);
} elseif ($_SESSION['rol'] != 'admin') {
    $sqlA .= " AND a.usuario_id = " . intval($_SESSION['usuario_id']);
}
$stmtA = $pdo->prepare($sqlA);
$stmtA->execute([$anioBase, $anioBase + 1, $anioBase, $anioBase + 1]);
$ausencias = $stmtA->fetchAll();

$mesesNombres = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'admin' ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto pb-20">
    <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
        <div class="text-center md:text-left">
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">Planificador CVTools</h1>
            <p class="text-slate-400 font-bold text-sm mt-2 italic">Ciclo 2026 — Enero 2027 • Benigànim</p>
        </div>
        
        <?php if($_SESSION['rol'] == 'admin'): ?>
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="p" value="calendario_anual">
            <select name="user_id" onchange="this.form.submit()" class="bg-white border-2 border-slate-100 p-4 rounded-3xl font-black text-[10px] uppercase shadow-xl outline-none focus:border-blue-500">
                <option value="all" <?php echo ($userId == 'all')?'selected':''; ?>>✨ VER EQUIPO COMPLETO</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <!-- REJILLA DE MESES (Iteramos 13 veces) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php 
        for($i=1; $i<=13; $i++): 
            $m = ($i > 12) ? 1 : $i;
            $anioActual = ($i > 12) ? $anioBase + 1 : $anioBase;
            
            $primerDia = date('N', strtotime("$anioActual-$m-01"));
            $diasMes = date('t', strtotime("$anioActual-$m-01"));
        ?>
        <div class="bg-white rounded-[40px] shadow-sm border border-slate-100 p-6 hover:shadow-2xl transition-all duration-500 relative <?php echo ($i > 12) ? 'ring-4 ring-blue-500/10' : ''; ?>">
            <h3 class="text-center font-black uppercase italic text-slate-800 mb-6 tracking-widest text-[10px] border-b border-slate-50 pb-3">
                <?php echo $mesesNombres[$m] . " " . $anioActual; ?>
            </h3>
            <div class="grid grid-cols-7 gap-1 text-[7px] text-center font-black text-slate-300 mb-3 uppercase">
                <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="grid grid-cols-7 gap-1">
                <?php for($h=1; $h<$primerDia; $h++) echo '<div></div>'; ?>
                <?php for($dia=1; $dia<=$diasMes; $dia++): 
                    $fechaC = sprintf("%s-%02d-%02d", $anioActual, $m, $dia);
                    $clase = "bg-slate-50 text-slate-300";
                    $label = ""; $nombres = [];

                    if(isset($festivos[$fechaC])) {
                        $f = $festivos[$fechaC];
                        if($f['descuenta_vacaciones']) $clase = "bg-rose-500 text-white shadow-lg shadow-rose-100";
                        else $clase = ($f['tipo'] == 'local') ? "bg-amber-400 text-white" : "bg-blue-600 text-white";
                        $label = $f['nombre'];
                    }

                    foreach($ausencias as $aus) {
                        if($fechaC >= $aus['fecha_inicio'] && $fechaC <= $aus['fecha_fin']) {
                            $clase = "bg-emerald-500 text-white shadow-lg shadow-emerald-100";
                            $nombres[] = explode(' ', $aus['emp_nombre'])[0];
                        }
                    }
                ?>
                    <div title="<?php echo $label . (count($nombres)?' - '.implode(', ',$nombres):''); ?>" class="aspect-square flex flex-col items-center justify-center rounded-xl text-[9px] font-black transition-all hover:scale-125 relative group <?php echo $clase; ?>">
                        <span><?php echo $dia; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- LEYENDA -->
    <div class="mt-16 flex flex-wrap justify-center gap-8 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 bg-white p-10 rounded-[45px] border border-slate-100 shadow-sm">
        <div class="flex items-center"><span class="w-4 h-4 bg-blue-600 rounded-lg mr-2"></span> Festivo Nacional</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-amber-400 rounded-lg mr-2"></span> Benigànim</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-rose-500 rounded-lg mr-2"></span> Cierre CVTools</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-emerald-500 rounded-lg mr-2"></span> Vacaciones Aprobadas</div>
    </div>
</div>
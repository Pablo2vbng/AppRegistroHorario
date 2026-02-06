<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$userId = ($_SESSION['rol'] == 'admin' && isset($_GET['user_id'])) ? $_GET['user_id'] : $_SESSION['usuario_id'];

// 1. Obtener Festivos
$stmtF = $pdo->prepare("SELECT * FROM festivos WHERE YEAR(fecha) = ?");
$stmtF->execute([$anio]);
$festivos = $stmtF->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

// 2. Obtener Ausencias Aprobadas del empleado
$stmtA = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND (YEAR(fecha_inicio) = ? OR YEAR(fecha_fin) = ?)");
$stmtA->execute([$userId, $anio, $anio]);
$ausencias = $stmtA->fetchAll();

// 3. Obtener lista de empleados para filtro Admin
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Planificador Anual <?php echo $anio; ?></h1>
            <p class="text-slate-400 font-bold text-sm">Visualización de festivos y ausencias</p>
        </div>
        
        <?php if($_SESSION['rol'] == 'admin'): ?>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="p" value="calendario_anual">
            <select name="user_id" onchange="this.form.submit()" class="bg-white border p-3 rounded-2xl font-bold text-xs uppercase shadow-sm">
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
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
            <h3 class="text-center font-black uppercase italic text-slate-800 mb-4 tracking-widest text-xs"><?php echo $nombreMes; ?></h3>
            
            <div class="grid grid-cols-7 gap-1 text-[8px] text-center font-black text-slate-300 mb-2 uppercase">
                <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <?php for($i=1; $i<$primerDia; $i++) echo '<div></div>'; ?>
                
                <?php for($dia=1; $dia<=$diasMes; $dia++): 
                    $fechaC = sprintf("%s-%02d-%02d", $anio, $m, $dia);
                    
                    $clase = "bg-slate-50 text-slate-400";
                    $tooltip = "";

                    // ¿Es Festivo?
                    if(isset($festivos[$fechaC])) {
                        $clase = ($festivos[$fechaC]['tipo'] == 'nacional') ? "bg-indigo-500 text-white" : "bg-blue-400 text-white";
                        $tooltip = $festivos[$fechaC]['nombre'];
                    }

                    // ¿Es Ausencia?
                    foreach($ausencias as $a) {
                        if($fechaC >= $a['fecha_inicio'] && $fechaC <= $a['fecha_fin']) {
                            $clase = ($a['tipo'] == 'vacaciones') ? "bg-emerald-500 text-white" : "bg-rose-500 text-white";
                            if($a['tipo'] == 'personal') $clase = "bg-amber-500 text-white";
                        }
                    }
                ?>
                    <div title="<?php echo $tooltip; ?>" class="aspect-square flex items-center justify-center rounded-lg text-[10px] font-black transition-all hover:scale-110 <?php echo $clase; ?>">
                        <?php echo $dia; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- LEYENDA -->
    <div class="mt-12 flex flex-wrap justify-center gap-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <div class="flex items-center"><span class="w-4 h-4 bg-indigo-500 rounded mr-2"></span> Festivo Nacional</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-blue-400 rounded mr-2"></span> Comunidad / Local</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-emerald-500 rounded mr-2"></span> Vacaciones</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-rose-500 rounded mr-2"></span> Baja Médica</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-amber-500 rounded mr-2"></span> Asuntos Propios</div>
    </div>
</div>
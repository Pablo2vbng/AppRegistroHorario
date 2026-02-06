<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$userId = ($_SESSION['rol'] == 'admin' && isset($_GET['user_id'])) ? $_GET['user_id'] : $_SESSION['usuario_id'];

// 1. Obtener Festivos y Cierres
$stmtF = $pdo->prepare("SELECT * FROM festivos WHERE YEAR(fecha) = ?");
$stmtF->execute([$anio]);
$festivos = $stmtF->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

// 2. Obtener Ausencias del empleado
$stmtA = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND (YEAR(fecha_inicio) = ? OR YEAR(fecha_fin) = ?)");
$stmtA->execute([$userId, $anio, $anio]);
$ausencias = $stmtA->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Calendario Laboral Benigànim <?php echo $anio; ?></h1>
        <p class="text-slate-400 font-bold text-sm">Visualización de festivos, cierres de empresa y mis días</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php for($m=1; $m<=12; $m++): 
            $primerDia = date('N', strtotime("$anio-$m-01"));
            $diasMes = date('t', strtotime("$anio-$m-01"));
        ?>
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
            <h3 class="text-center font-black uppercase text-slate-800 mb-4 text-[10px] tracking-widest italic"><?php echo date('F', mktime(0,0,0,$m,1)); ?></h3>
            <div class="grid grid-cols-7 gap-1">
                <?php for($i=1; $i<$primerDia; $i++) echo '<div></div>'; ?>
                <?php for($dia=1; $dia<=$diasMes; $dia++): 
                    $fechaC = sprintf("%s-%02d-%02d", $anio, $m, $dia);
                    $clase = "bg-slate-50 text-slate-300";
                    $label = "";

                    // LÓGICA DE COLORES
                    if(isset($festivos[$fechaC])) {
                        $f = $festivos[$fechaC];
                        if($f['descuenta_vacaciones']) {
                            $clase = "bg-rose-500 text-white shadow-lg shadow-rose-100"; // CIERRE EMPRESA
                            $label = $f['nombre'];
                        } else {
                            $clase = ($f['tipo'] == 'local') ? "bg-amber-400 text-white" : "bg-blue-500 text-white"; // FESTIVOS GRATIS
                            $label = $f['nombre'];
                        }
                    }

                    foreach($ausencias as $aus) {
                        if($fechaC >= $aus['fecha_inicio'] && $fechaC <= $aus['fecha_fin']) {
                            $clase = "bg-emerald-500 text-white shadow-lg shadow-emerald-100"; // MIS VACACIONES
                        }
                    }
                ?>
                    <div title="<?php echo $label; ?>" class="aspect-square flex items-center justify-center rounded-lg text-[10px] font-black <?php echo $clase; ?>">
                        <?php echo $dia; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- LEYENDA CLARA -->
    <div class="mt-12 flex flex-wrap justify-center gap-8 text-[10px] font-black uppercase tracking-widest text-slate-400 bg-white p-6 rounded-3xl border border-slate-100">
        <div class="flex items-center"><span class="w-4 h-4 bg-blue-500 rounded-md mr-2"></span> Festivo Nacional</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-amber-400 rounded-md mr-2"></span> Festivo Benigànim</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-rose-500 rounded-md mr-2"></span> Cierre Empresa (Resta Vacaciones)</div>
        <div class="flex items-center"><span class="w-4 h-4 bg-emerald-500 rounded-md mr-2"></span> Mis Vacaciones Elegidas</div>
    </div>
</div>
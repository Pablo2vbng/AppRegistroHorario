<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. CÁLCULOS DE PRODUCTIVIDAD (Para el Gráfico de Donut)
$mesActual = date('m'); $anioActual = date('Y'); $hoyDia = date('d');
$diasLaborablesPasados = 0;
for($d=1; $d<=$hoyDia; $d++) {
    $fechaEval = "$anioActual-$mesActual-".str_pad($d, 2, '0', STR_PAD_LEFT);
    if(date('N', strtotime($fechaEval)) < 6) {
        $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ? AND descuenta_vacaciones = 0");
        $stF->execute([$fechaEval]);
        if(!$stF->fetch()) $diasLaborablesPasados++;
    }
}

// Horas Teóricas (Objetivo)
$horasObjetivo = $diasLaborablesPasados * 8; 
if($esAdmin) {
    $numEmps = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol != 'admin'")->fetchColumn();
    $horasObjetivo *= $numEmps;
}

// Horas Reales (Fichadas)
$sqlR = "SELECT fecha_hora, tipo FROM fichajes WHERE MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ?";
if(!$esAdmin) $sqlR .= " AND usuario_id = " . intval($miId);
$stmtR = $pdo->prepare($sqlR); $stmtR->execute([$mesActual, $anioActual]);
$fichajesR = $stmtR->fetchAll();
$segundos = 0; $start = null;
foreach($fichajesR as $f) {
    if($f['tipo']=='entrada' || $f['tipo']=='reanudar') $start = strtotime($f['fecha_hora']);
    if(($f['tipo']=='pausa' || $f['tipo']=='salida') && $start) { $segundos += (strtotime($f['fecha_hora']) - $start); $start = null; }
}
$horasReales = round($segundos / 3600, 1);

// 3. Info Admin
$pendientes = 0; $estadoPlantilla = []; $ausenciasHoy = [];
if ($esAdmin) {
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.nombre, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol != 'admin' ORDER BY u.nombre ASC")->fetchAll();
    $ausenciasHoy = $pdo->query("SELECT u.nombre, a.tipo FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND CURDATE() BETWEEN a.fecha_inicio AND a.fecha_fin")->fetchAll();
}

// 4. Estado Fichaje Trabajador
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}
?>

<div class="max-w-7xl mx-auto pb-20">
    
    <!-- HEADER -->
    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-8">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-2">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.3em] italic">Benigànim • Sede Central</p>
        </div>
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/10 rounded-full blur-[100px]"></div>
    </div>

    <!-- BALANCE Y FICHAJE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- PRODUCTIVIDAD (DONUT) -->
        <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col items-center">
            <h3 class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-8">Balance Horario del Mes</h3>
            <div class="relative w-full max-w-[200px]">
                <canvas id="chartProd"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <p class="text-3xl font-black text-slate-800 italic"><?php echo ($horasObjetivo > 0) ? round(($horasReales/$horasObjetivo)*100) : 0; ?>%</p>
                </div>
            </div>
            <div class="mt-8 w-full grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-3 rounded-2xl border text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase">Objetivo</p>
                    <p class="font-black text-slate-800"><?php echo $horasObjetivo; ?>h</p>
                </div>
                <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-100 text-center">
                    <p class="text-[8px] font-black text-emerald-600 uppercase">Logrado</p>
                    <p class="font-black text-emerald-700"><?php echo $horasReales; ?>h</p>
                </div>
            </div>
        </div>

        <?php if(!$esAdmin): ?>
            <!-- FICHAJE TRABAJADOR -->
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center flex flex-col justify-center">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[40px] font-black text-2xl active:scale-95 transition flex flex-col items-center"><i class="fas fa-play mb-3"></i> ENTRAR</button>
                    <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[40px] font-black text-2xl active:scale-95 transition flex flex-col items-center"><i class="fas fa-pause mb-3"></i> PAUSA</button>
                    <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[40px] font-black text-2xl active:scale-95 transition flex flex-col items-center"><i class="fas fa-power-off mb-3"></i> SALIR</button>
                </div>
                <div class="mt-8 text-[9px] font-black text-slate-300 uppercase italic tracking-widest">Saldo Vacaciones Libres: <span class="text-blue-600"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?> días</span></div>
            </div>
        <?php else: ?>
            <!-- MONITOR ADMIN -->
            <div class="lg:col-span-2 space-y-10">
                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 md:p-12">
                    <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-10 flex items-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 animate-pulse"></span> Monitor de Plantilla
                    </h2>
                    <div class="grid grid-cols-3 md:grid-cols-5 gap-8">
                        <?php foreach($estadoPlantilla as $p): 
                            $color = ($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-400' : 'bg-slate-200');
                        ?>
                        <div class="flex flex-col items-center group">
                            <div class="relative mb-3">
                                <div class="w-16 h-16 rounded-[24px] bg-slate-50 border-2 border-slate-100 flex items-center justify-center font-black text-slate-300 text-xl group-hover:scale-110 transition duration-300"><?php echo substr($p['nombre'], 0, 1); ?></div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $color; ?> border-2 border-white rounded-full shadow-md"></div>
                            </div>
                            <p class="text-[10px] font-black text-slate-700 uppercase"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
new Chart(document.getElementById('chartProd'), {
    type: 'doughnut',
    data: { datasets: [{ data: [<?php echo $horasReales; ?>, <?php echo max(0, $horasObjetivo - $horasReales); ?>], backgroundColor: ['#10b981', '#f1f5f9'], borderWidth: 0, cutout: '85%', borderRadius: 20 }] },
    options: { plugins: { legend: { display: false } } }
});
function fichar(tipo) {
    Swal.fire({ title: 'Localizando GPS...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}` })
        .then(res => res.json()).then(data => location.reload());
    }, err => { Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Activa la ubicación para fichar.' }); });
}
</script>
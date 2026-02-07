<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. DATOS DEL USUARIO LOGUEADO
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. LÓGICA DE PRODUCTIVIDAD (Común para el gráfico Donut)
$mesActual = date('m'); $anioActual = date('Y'); $hoyDia = date('d');
$diasLaborablesPasados = 0;
for($d=1; $d<=$hoyDia; $d++) {
    $fechaEval = "$anioActual-$mesActual-".str_pad($d, 2, '0', STR_PAD_LEFT);
    if(date('N', strtotime($fechaEval)) < 6) { // Lunes a Viernes
        $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ? AND descuenta_vacaciones = 0");
        $stF->execute([$fechaEval]);
        if(!$stF->fetch()) $diasLaborablesPasados++;
    }
}

// 3. LÓGICA ESPECÍFICA PARA CARMEN (ADMIN)
if ($esAdmin) {
    // Filtro de empleado para el gráfico
    $filtroEmp = $_GET['ver_emp'] ?? 'all';
    $numEmps = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol != 'admin'")->fetchColumn();
    $factor = ($filtroEmp == 'all') ? $numEmps : 1;
    $horasObjetivo = $diasLaborablesPasados * 8 * $factor;

    // Cálculo Horas Reales
    $sqlR = "SELECT fecha_hora, tipo FROM fichajes WHERE MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ?";
    if($filtroEmp != 'all') $sqlR .= " AND usuario_id = " . intval($filtroEmp);
    $stmtR = $pdo->prepare($sqlR); $stmtR->execute([$mesActual, $anioActual]);
    $fichajesR = $stmtR->fetchAll();
    $segundos = 0; $start = null;
    foreach($fichajesR as $f) {
        if($f['tipo']=='entrada' || $f['tipo']=='reanudar') $start = strtotime($f['fecha_hora']);
        if(($f['tipo']=='pausa' || $f['tipo']=='salida') && $start) { $segundos += (strtotime($f['fecha_hora']) - $start); $start = null; }
    }
    $horasReales = round($segundos / 3600, 1);

    // Monitor, Solicitudes y Novedades
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.id, u.nombre, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol != 'admin' ORDER BY u.nombre ASC")->fetchAll();
    $usuariosLista = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'admin' ORDER BY nombre ASC")->fetchAll();
    $ausenciasHoy = $pdo->query("SELECT u.nombre, a.tipo FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND CURDATE() BETWEEN a.fecha_inicio AND a.fecha_fin")->fetchAll();
    $festivoHoy = $pdo->query("SELECT nombre FROM festivos WHERE fecha = CURDATE()")->fetchColumn();
} 

// 4. LÓGICA ESPECÍFICA PARA EL TRABAJADOR (PABLO/JUDITH)
else {
    $horasObjetivo = $diasLaborablesPasados * 8;
    $stmtR = $pdo->prepare("SELECT fecha_hora, tipo FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ?");
    $stmtR->execute([$miId, $mesActual, $anioActual]);
    $fichajesR = $stmtR->fetchAll();
    $segundos = 0; $start = null;
    foreach($fichajesR as $f) {
        if($f['tipo']=='entrada' || $f['tipo']=='reanudar') $start = strtotime($f['fecha_hora']);
        if(($f['tipo']=='pausa' || $f['tipo']=='salida') && $start) { $segundos += (strtotime($f['fecha_hora']) - $start); $start = null; }
    }
    $horasReales = round($segundos / 3600, 1);

    $notificaciones = $pdo->prepare("SELECT id, tipo, estado FROM ausencias WHERE usuario_id = ? AND notificacion_vista = 0 AND estado != 'pendiente'");
    $notificaciones->execute([$miId]);
    $notifList = $notificaciones->fetchAll();

    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}

$nextFest = $pdo->query("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1")->fetch();
?>

<div class="max-w-7xl mx-auto pb-20">

    <!-- AVISOS DE RESPUESTA (Solo Trabajador) -->
    <?php if(!$esAdmin): foreach($notifList as $n): ?>
        <div class="bg-white border-l-8 <?php echo ($n['estado']=='aprobado')?'border-emerald-500':'border-rose-500'; ?> p-6 rounded-3xl mb-6 shadow-xl flex justify-between items-center animate-bounce">
            <div class="flex items-center gap-4">
                <i class="fas fa-bell <?php echo ($n['estado']=='aprobado')?'text-emerald-500':'text-rose-500'; ?> text-xl"></i>
                <p class="text-sm font-bold text-slate-800">Carmen ha <span class="underline"><?php echo strtoupper($n['estado']); ?></span> tu petición de <?php echo strtoupper($n['tipo']); ?>.</p>
            </div>
            <button onclick="marcarLeida(<?php echo $n['id']; ?>)" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase">Ok</button>
        </div>
    <?php endforeach; endif; ?>

    <!-- BIENVENIDA -->
    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.4em] italic">Benigànim • Centro de Control</p>
        </div>
        <?php if($nextFest): ?>
            <div class="relative z-10 bg-white/5 backdrop-blur-xl px-6 py-4 rounded-[30px] border border-white/10 text-center">
                <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest leading-none mb-1">Próximo Festivo</p>
                <p class="font-black text-sm uppercase italic"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> — <?php echo $nextFest['nombre']; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- VISTA CARMEN (ADMIN) -->
    <?php if($esAdmin): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- ANALÍTICA PRODUCTIVIDAD -->
            <div class="lg:col-span-1 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col items-center">
                <form method="GET" class="w-full mb-8">
                    <input type="hidden" name="p" value="dashboard">
                    <select name="ver_emp" onchange="this.form.submit()" class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-2xl font-black text-xs uppercase outline-none focus:border-blue-500">
                        <option value="all">✨ TODA LA PLANTILLA</option>
                        <?php foreach($usuariosLista as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($filtroEmp == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="relative w-full aspect-square max-w-[200px]">
                    <canvas id="chartProd"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <p class="text-3xl font-black text-slate-800 italic"><?php echo ($horasObjetivo > 0) ? round(($horasReales/$horasObjetivo)*100) : 0; ?>%</p>
                        <p class="text-[9px] font-black text-slate-300 uppercase">Eficiencia</p>
                    </div>
                </div>
                <div class="mt-8 w-full space-y-3">
                    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border"><span class="text-[9px] font-black text-slate-400 uppercase">Contrato</span><span class="font-black text-slate-700"><?php echo $horasObjetivo; ?>h</span></div>
                    <div class="flex justify-between items-center bg-emerald-50 p-4 rounded-2xl border border-emerald-100"><span class="text-[9px] font-black text-emerald-600 uppercase">Realizado</span><span class="font-black text-emerald-700"><?php echo $horasReales; ?>h</span></div>
                </div>
            </div>

            <!-- SITUACIÓN Y MURO -->
            <div class="lg:col-span-2 space-y-10">
                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex-1">
                        <h2 class="text-sm font-black text-slate-800 uppercase italic mb-6">Situación Hoy</h2>
                        <?php if($festivoHoy): ?><div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-blue-800 font-bold text-xs mb-2 italic">HOY ES: <?php echo strtoupper($festivoHoy); ?></div><?php endif; ?>
                        <?php foreach($ausenciasHoy as $a): ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-2">
                                <span class="font-black text-slate-700 text-xs italic"><?php echo $a['nombre']; ?></span>
                                <span class="text-[9px] font-black uppercase text-rose-500"><?php echo $a['tipo']; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($ausenciasHoy) && !$festivoHoy): echo '<p class="text-slate-300 italic text-xs">Sin ausencias registradas hoy</p>'; endif; ?>
                    </div>
                    <?php if($pendientes > 0): ?>
                        <a href="index.php?p=gestion_ausencias" class="bg-rose-500 text-white p-10 rounded-[40px] shadow-2xl text-center group transition hover:scale-105">
                            <p class="text-4xl font-black italic mb-1"><?php echo $pendientes; ?></p>
                            <p class="text-[9px] font-black uppercase tracking-widest">Validar Peticiones</p>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 md:p-12">
                    <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-10 flex items-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 animate-pulse"></span> Equipo en Benigànim
                    </h2>
                    <div class="grid grid-cols-3 md:grid-cols-5 gap-8 text-center">
                        <?php foreach($estadoPlantilla as $p): 
                            $color = ($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-400' : 'bg-slate-200');
                        ?>
                        <div class="flex flex-col items-center group">
                            <div class="relative mb-3">
                                <div class="w-16 h-16 rounded-[24px] bg-slate-50 border-2 border-slate-100 flex items-center justify-center font-black text-slate-200 text-xl group-hover:scale-110 transition duration-300"><?php echo substr($p['nombre'], 0, 1); ?></div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $color; ?> border-2 border-white rounded-full shadow-md"></div>
                            </div>
                            <p class="text-[10px] font-black text-slate-700 uppercase italic"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- VISTA TRABAJADOR (PABLO/JUDITH) -->
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- PANEL FICHAJE -->
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center">
                <h2 class="text-[10px] font-black mb-10 uppercase tracking-[0.5em] text-slate-300 italic">Validación por GPS</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-play mb-4"></i> ENTRAR</button>
                    <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-pause mb-4"></i> PAUSA</button>
                    <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-power-off mb-4"></i> SALIR</button>
                </div>
                <div class="mt-8 text-[10px] font-black text-slate-300 uppercase italic">Estado: <span class="text-slate-900 border-b-2 border-emerald-500 pb-1"><?php echo $miEstadoActual; ?></span></div>
            </div>

            <!-- PRODUCTIVIDAD Y SOLICITUDES -->
            <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col items-center justify-between">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 italic">Mi Rendimiento Mensual</p>
                <div class="relative w-full aspect-square max-w-[160px] mb-6">
                    <canvas id="chartProdWorker"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center font-black text-2xl text-slate-800 italic">
                        <?php echo ($horasObjetivo > 0) ? round(($horasReales/$horasObjetivo)*100) : 0; ?>%
                    </div>
                </div>
                <div class="w-full space-y-4">
                    <div class="bg-blue-600 text-white p-6 rounded-[35px] shadow-xl text-center">
                        <p class="text-5xl font-black tracking-tighter mb-1"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?></p>
                        <p class="text-[9px] font-black uppercase tracking-widest opacity-70">Días Libres</p>
                    </div>
                    <a href="index.php?p=solicitudes" class="block text-center bg-slate-900 text-white py-5 rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-lg hover:bg-emerald-600 transition">Nueva Solicitud</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Gráficos Donut
<?php if($esAdmin): ?>
    new Chart(document.getElementById('chartProd'), { type: 'doughnut', data: { datasets: [{ data: [<?php echo $horasReales; ?>, <?php echo max(0, $horasObjetivo - $horasReales); ?>], backgroundColor: ['#10b981', '#f1f5f9'], borderWidth: 0, cutout: '85%', borderRadius: 20 }] }, options: { plugins: { legend: { display: false } }, animation: { duration: 2000, easing: 'easeOutQuart' } } });
<?php else: ?>
    new Chart(document.getElementById('chartProdWorker'), { type: 'doughnut', data: { datasets: [{ data: [<?php echo $horasReales; ?>, <?php echo max(0, $horasObjetivo - $horasReales); ?>], backgroundColor: ['#10b981', '#f1f5f9'], borderWidth: 0, cutout: '85%', borderRadius: 15 }] }, options: { plugins: { legend: { display: false } } } });
<?php endif; ?>

// GPS y Notificaciones
function fichar(tipo) {
    Swal.fire({ title: 'Localizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}` })
        .then(res => res.json()).then(data => location.reload());
    }, err => { Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Activa la ubicación para fichar.' }); });
}
function marcarLeida(id) {
    fetch('api/notificacion_leida.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}` }).then(() => location.reload());
}
</script>
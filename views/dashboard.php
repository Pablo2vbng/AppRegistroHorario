<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos del Usuario Logueado
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. LÓGICA CARMEN (ADMIN) - PRODUCTIVIDAD Y EQUIPO
if ($esAdmin) {
    // Empleado seleccionado para el gráfico (por defecto 'all')
    $filtroEmp = $_GET['ver_emp'] ?? 'all';
    $mesActual = date('m');
    $anioActual = date('Y');

    // --- CÁLCULO DE HORAS TEÓRICAS (Lo que deben trabajar) ---
    // Contamos días de Lunes a Viernes que han pasado este mes y NO son festivos "gratis"
    $hoyDia = date('d');
    $horasTeoricasTotales = 0;
    $diasLaborablesPasados = 0;

    for($d=1; $d<=$hoyDia; $d++) {
        $fechaEval = "$anioActual-$mesActual-".str_pad($d, 2, '0', STR_PAD_LEFT);
        $w = date('N', strtotime($fechaEval));
        if($w < 6) { // Lunes a Viernes
            // Mirar si es festivo "gratis" (que no descuenta vacaciones)
            $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ? AND descuenta_vacaciones = 0");
            $stF->execute([$fechaEval]);
            if(!$stF->fetch()) $diasLaborablesPasados++;
        }
    }
    
    // Si es 'all', multiplicamos por el número de empleados (menos admins)
    $numEmps = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol != 'admin'")->fetchColumn();
    $factor = ($filtroEmp == 'all') ? $numEmps : 1;
    $horasTeoricasObjetivo = $diasLaborablesPasados * 8 * $factor;

    // --- CÁLCULO DE HORAS REALES (Lo que han fichado) ---
    $sqlReal = "SELECT fecha_hora, tipo FROM fichajes WHERE MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ?";
    if($filtroEmp != 'all') $sqlReal .= " AND usuario_id = " . intval($filtroEmp);
    
    $stmtR = $pdo->prepare($sqlReal);
    $stmtR->execute([$mesActual, $anioActual]);
    $fichajesR = $stmtR->fetchAll();

    $segundosReales = 0; $inicio = null;
    foreach($fichajesR as $f) {
        if($f['tipo'] == 'entrada' || $f['tipo'] == 'reanudar') $inicio = strtotime($f['fecha_hora']);
        if(($f['tipo'] == 'pausa' || $f['tipo'] == 'salida') && $inicio) {
            $segundosReales += (strtotime($f['fecha_hora']) - $inicio); $inicio = null;
        }
    }
    $horasRealesHechas = round($segundosReales / 3600, 1);

    // Muro de Presencia y Pendientes
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.id, u.nombre, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol != 'admin' ORDER BY u.nombre ASC")->fetchAll();
    $usuariosLista = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol != 'admin' ORDER BY nombre ASC")->fetchAll();
}

// 3. LÓGICA TRABAJADOR
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}
?>

<div class="max-w-7xl mx-auto pb-20">
    
    <!-- HEADER -->
    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.4em] italic">Benigànim • Inteligencia RRHH</p>
        </div>
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/10 rounded-full blur-[100px]"></div>
    </div>

    <?php if($esAdmin): ?>
        <!-- ==========================================
             VISTA CARMEN: ANALÍTICA DE PRODUCTIVIDAD
             ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-10">
            
            <!-- SELECTOR Y GRÁFICO DONUT -->
            <div class="lg:col-span-1 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col items-center">
                <form method="GET" class="w-full mb-8">
                    <input type="hidden" name="p" value="dashboard">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center">Analizar Productividad</label>
                    <select name="ver_emp" onchange="this.form.submit()" class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-2xl font-black text-xs uppercase outline-none focus:border-blue-500 transition">
                        <option value="all">✨ TODA LA PLANTILLA</option>
                        <?php foreach($usuariosLista as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($filtroEmp == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="relative w-full aspect-square max-w-[220px]">
                    <canvas id="chartProductividad"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <p class="text-[10px] font-black text-slate-300 uppercase">Cumplimiento</p>
                        <p class="text-3xl font-black text-slate-800 italic"><?php echo ($horasTeoricasObjetivo > 0) ? round(($horasRealesHechas/$horasTeoricasObjetivo)*100) : 0; ?>%</p>
                    </div>
                </div>

                <div class="mt-8 w-full space-y-3">
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Horas Contrato</span>
                        <span class="font-black text-slate-700"><?php echo $horasTeoricasObjetivo; ?>h</span>
                    </div>
                    <div class="flex justify-between items-center bg-emerald-50 p-3 rounded-xl border border-emerald-100">
                        <span class="text-[10px] font-bold text-emerald-600 uppercase">Horas Reales</span>
                        <span class="font-black text-emerald-700"><?php echo $horasRealesHechas; ?>h</span>
                    </div>
                </div>
            </div>

            <!-- MURO DE PRESENCIA -->
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 md:p-12">
                <div class="flex justify-between items-center mb-10">
                    <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest flex items-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 animate-pulse"></span>
                        Estado Actual en Benigànim
                    </h2>
                    <?php if($pendientes > 0): ?>
                        <a href="index.php?p=gestion_ausencias" class="bg-rose-500 text-white px-4 py-2 rounded-xl text-[9px] font-black uppercase animate-bounce">
                            <?php echo $pendientes; ?> Peticiones
                        </a>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-8">
                    <?php foreach($estadoPlantilla as $p): 
                        $color = ($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-400' : 'bg-slate-200');
                    ?>
                    <div class="flex flex-col items-center group">
                        <div class="relative mb-3">
                            <div class="w-16 h-16 rounded-[24px] bg-slate-50 border-2 border-slate-100 flex items-center justify-center font-black text-slate-300 text-xl group-hover:scale-110 transition duration-300">
                                <?php echo substr($p['nombre'], 0, 1); ?>
                            </div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $color; ?> border-2 border-white rounded-full shadow-md"></div>
                        </div>
                        <p class="text-[10px] font-black text-slate-700 uppercase tracking-tighter"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        new Chart(document.getElementById('chartProductividad'), {
            type: 'doughnut',
            data: {
                labels: ['Horas Reales', 'Pendiente Contrato'],
                datasets: [{
                    data: [<?php echo $horasRealesHechas; ?>, <?php echo max(0, $horasTeoricasObjetivo - $horasRealesHechas); ?>],
                    backgroundColor: ['#10b981', '#f1f5f9'],
                    borderWidth: 0, cutout: '85%', borderRadius: 20
                }]
            },
            options: { plugins: { legend: { display: false } }, animation: { duration: 2000, easing: 'easeOutQuart' } }
        });
        </script>

    <?php else: ?>
        <!-- ==========================================
             VISTA TRABAJADOR: PABLO / JUDITH
             ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center">
                <h2 class="text-[10px] font-black mb-10 uppercase tracking-[0.4em] text-slate-300 italic">Registro con Ubicación GPS</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                        <i class="fas fa-play mb-3"></i> ENTRAR
                    </button>
                    <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                        <i class="fas fa-pause mb-3"></i> PAUSA
                    </button>
                    <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                        <i class="fas fa-power-off mb-3"></i> SALIR
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Saldo Vacacional</p>
                    <p class="text-7xl font-black text-blue-600 tracking-tighter mb-2"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?></p>
                    <p class="text-[10px] font-bold text-slate-300 uppercase italic">Días disponibles</p>
                </div>
                <a href="index.php?p=solicitudes" class="mt-8 block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-600 transition">Gestionar</a>
            </div>
        </div>

        <script>
        function fichar(tipo) {
            Swal.fire({ title: 'Localizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            navigator.geolocation.getCurrentPosition(pos => {
                fetch('api/fichar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
                }).then(res => res.json()).then(data => location.reload());
            }, err => { Swal.fire({ icon: 'error', title: 'Ubicación Requerida', text: 'Activa el GPS para fichar en Benigànim.' }); });
        }
        </script>
    <?php endif; ?>

</div>
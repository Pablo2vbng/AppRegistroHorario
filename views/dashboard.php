<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos del Usuario Logueado
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. LÓGICA PARA CARMEN (ADMIN) - MONITORIZACIÓN
$pendientes = 0; $estadoPlantilla = []; $ausenciasHoy = []; $festivoHoy = null;
$stats = ['total' => 0, 'activos' => 0, 'pausa' => 0];

if ($esAdmin) {
    // Solicitudes pendientes (Burbuja)
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();

    // Muro de Presencia (Trabajadores que NO son admin)
    $sqlPresencia = "
        SELECT u.id, u.nombre, f.tipo as ultimo_estado 
        FROM usuarios u 
        LEFT JOIN (
            SELECT f1.usuario_id, f1.tipo 
            FROM fichajes f1 
            WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())
        ) f ON u.id = f.usuario_id 
        WHERE u.rol != 'admin' ORDER BY u.nombre ASC";
    $estadoPlantilla = $pdo->query($sqlPresencia)->fetchAll();

    foreach($estadoPlantilla as $p) {
        if($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') $stats['activos']++;
        if($p['ultimo_estado'] == 'pausa') $stats['pausa']++;
        $stats['total']++;
    }

    // Quién está ausente HOY (Vacaciones, Médico, etc.)
    $sqlAusHoy = "SELECT u.nombre, a.tipo FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND CURDATE() BETWEEN a.fecha_inicio AND a.fecha_fin";
    $ausenciasHoy = $pdo->query($sqlAusHoy)->fetchAll();

    // Festivo de hoy en Benigànim
    $festivoHoy = $pdo->query("SELECT nombre FROM festivos WHERE fecha = CURDATE()")->fetchColumn();
}

// 3. LÓGICA PARA TRABAJADORES (Fichaje)
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}

// 4. Próximo evento calendario
$nextEvent = $pdo->query("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1")->fetch();
?>

<div class="max-w-7xl mx-auto pb-20">
    
    <!-- HEADER DINÁMICO -->
    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-2">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.4em] italic">Benigànim • Sede Central</p>
        </div>
        
        <?php if($nextEvent): ?>
            <div class="relative z-10 bg-white/5 backdrop-blur-xl px-6 py-4 rounded-[30px] border border-white/10 flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center text-blue-400"><i class="fas fa-calendar-day"></i></div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest leading-none">Próximo Festivo</p>
                    <p class="font-black text-sm uppercase italic"><?php echo date('d M', strtotime($nextEvent['fecha'])); ?> — <?php echo $nextEvent['nombre']; ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/10 rounded-full blur-[100px]"></div>
    </div>

    <?php if($esAdmin): ?>
        <!-- ==========================================
             DASHBOARD CARMEN (ADMIN)
             ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- COLUMNA IZQUIERDA: MURO DE PRESENCIA -->
            <div class="lg:col-span-2 space-y-10">
                <!-- CARDS RÁPIDAS -->
                <div class="grid grid-cols-3 gap-4 md:gap-8">
                    <div class="bg-white p-6 rounded-[35px] border border-slate-200 text-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Dentro</p>
                        <p class="text-3xl font-black text-emerald-500 italic"><?php echo $stats['activos']; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-[35px] border border-slate-200 text-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Pausa</p>
                        <p class="text-3xl font-black text-amber-500 italic"><?php echo $stats['pausa']; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-[35px] border border-slate-200 text-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Total</p>
                        <p class="text-3xl font-black text-slate-800 italic"><?php echo $stats['total']; ?></p>
                    </div>
                </div>

                <!-- MURO VISUAL -->
                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-8 md:p-12">
                    <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-10 flex items-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 animate-pulse"></span>
                        Plantilla en Activo Ahora
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <?php foreach($estadoPlantilla as $p): 
                            $colorDot = ($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-500' : 'bg-slate-200');
                        ?>
                        <div class="flex flex-col items-center group">
                            <div class="relative mb-3">
                                <div class="w-16 h-16 rounded-[25px] bg-slate-50 border-2 border-slate-100 flex items-center justify-center font-black text-slate-300 text-xl group-hover:scale-110 transition duration-300">
                                    <?php echo substr($p['nombre'], 0, 1); ?>
                                </div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $colorDot; ?> border-2 border-white rounded-full shadow-md"></div>
                            </div>
                            <p class="text-[10px] font-black text-slate-700 uppercase"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: NOVEDADES DEL DÍA -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 p-10">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 border-b pb-4">Situación Hoy en Benigànim</h3>
                    
                    <div class="space-y-6">
                        <!-- Festivo Hoy -->
                        <?php if($festivoHoy): ?>
                        <div class="flex items-center gap-4 bg-blue-50 p-4 rounded-2xl border border-blue-100">
                            <i class="fas fa-glass-cheers text-blue-500"></i>
                            <p class="text-[10px] font-black text-blue-800 uppercase italic">Hoy es: <?php echo $festivoHoy; ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Ausencias Activas -->
                        <?php if(empty($ausenciasHoy)): ?>
                            <div class="py-10 text-center opacity-30">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p class="text-[10px] font-black uppercase">Toda la plantilla está activa</p>
                            </div>
                        <?php else: ?>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Personal ausente:</p>
                            <?php foreach($ausenciasHoy as $a): 
                                $color = ($a['tipo'] == 'medico') ? 'text-rose-500' : 'text-emerald-500';
                            ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="font-black text-slate-700 text-xs"><?php echo $a['nombre']; ?></span>
                                <span class="<?php echo $color; ?> text-[9px] font-black uppercase italic"><?php echo $a['tipo']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notificaciones Carmen -->
                <?php if($pendientes > 0): ?>
                <a href="index.php?p=gestion_ausencias" class="block bg-rose-500 p-8 rounded-[40px] text-white shadow-xl shadow-rose-100 group transition-all hover:bg-rose-600">
                    <div class="flex justify-between items-center mb-2">
                        <i class="fas fa-envelope-open-text text-2xl group-hover:rotate-12 transition"></i>
                        <span class="text-2xl font-black italic"><?php echo $pendientes; ?></span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest">Peticiones por Validar</p>
                </a>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- ==========================================
             DASHBOARD TRABAJADOR (PABLO/JUDITH)
             ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- PANEL FICHAJE GPS -->
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center">
                <h2 class="text-[10px] font-black mb-10 uppercase tracking-[0.4em] text-slate-300 italic">Validación GPS Benigànim</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                        class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                        <i class="fas fa-play mb-3 group-hover:scale-110 transition"></i> ENTRAR
                    </button>
                    <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                        class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                        <i class="fas fa-pause mb-3 group-hover:scale-110 transition"></i> PAUSA
                    </button>
                    <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> 
                        class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                        <i class="fas fa-power-off mb-3 group-hover:scale-110 transition"></i> SALIR
                    </button>
                </div>
                <div class="mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tu Estado: <span class="text-slate-900 border-b-2 border-emerald-500 pb-1 uppercase"><?php echo $miEstadoActual; ?></span></div>
            </div>

            <!-- BOLSA VACACIONES TRABAJADOR -->
            <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between relative overflow-hidden">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Bolsa Vacacional</p>
                    <p class="text-7xl font-black text-blue-600 tracking-tighter mb-2"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?></p>
                    <p class="text-[10px] font-bold text-slate-300 uppercase italic">Días disponibles para pedir</p>
                </div>
                <a href="index.php?p=solicitudes" class="mt-8 block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-600 transition duration-300">Pedir mis días</a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function fichar(tipo) {
    Swal.fire({ title: 'Localizando...', text: 'Validando posición GPS en Benigànim', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
        }).then(res => res.json()).then(data => location.reload());
    }, err => {
        Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Activa la ubicación para fichar en CVTools.' });
    });
}
</script>
<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos del Usuario Logueado
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. LÓGICA EXCLUSIVA PARA CARMEN (ADMIN)
$pendientes = 0; $estadoPlantilla = []; $stats = ['total' => 0, 'activos' => 0, 'pausa' => 0];
if ($esAdmin) {
    // Conteo de solicitudes pendientes
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();

    // Monitor de Presencia en tiempo real
    $sqlPresencia = "
        SELECT u.id, u.nombre, u.horario, f.tipo as ultimo_estado, f.fecha_hora as ultima_hora
        FROM usuarios u
        LEFT JOIN (
            SELECT f1.usuario_id, f1.tipo, f1.fecha_hora
            FROM fichajes f1
            WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())
        ) f ON u.id = f.usuario_id
        WHERE u.rol != 'admin'
        ORDER BY u.nombre ASC";
    $estadoPlantilla = $pdo->query($sqlPresencia)->fetchAll();

    // Estadísticas rápidas para las "Cards"
    $stats['total'] = count($estadoPlantilla);
    foreach($estadoPlantilla as $p) {
        if($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') $stats['activos']++;
        if($p['ultimo_estado'] == 'pausa') $stats['pausa']++;
    }
}

// 3. LÓGICA PARA TRABAJADORES (Fichaje)
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}

// 4. Próximo festivo en Benigànim
$stmtNext = $pdo->prepare("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
$stmtNext->execute();
$nextFest = $stmtNext->fetch();
?>

<div class="max-w-7xl mx-auto pb-20">
    
    <!-- HEADER BIENVENIDA -->
    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-2">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.4em] italic">Sede Benigànim • Registro Digital</p>
        </div>
        
        <?php if($nextFest): ?>
            <div class="relative z-10 bg-white/5 backdrop-blur-xl px-6 py-4 rounded-[30px] border border-white/10 flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center text-blue-400"><i class="fas fa-calendar-star"></i></div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest">Próximo Festivo</p>
                    <p class="font-black text-sm uppercase italic"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> — <?php echo $nextFest['nombre']; ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/10 rounded-full blur-[100px]"></div>
    </div>

    <!-- VISTA CARMEN (ADMIN) -->
    <?php if($esAdmin): ?>
        
        <!-- CARDS ESTADÍSTICAS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-emerald-400 transition-all duration-500">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Activos Ahora</p>
                    <p class="text-4xl font-black text-emerald-500 italic"><?php echo $stats['activos']; ?></p>
                </div>
                <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500 text-xl"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-amber-400 transition-all duration-500">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">En Pausa</p>
                    <p class="text-4xl font-black text-amber-500 italic"><?php echo $stats['pausa']; ?></p>
                </div>
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500 text-xl"><i class="fas fa-mug-hot"></i></div>
            </div>
            <div class="bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-blue-400 transition-all duration-500">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Plantilla Total</p>
                    <p class="text-4xl font-black text-slate-800 italic"><?php echo $stats['total']; ?></p>
                </div>
                <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 text-xl"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <!-- MURO DE PRESENCIA VISUAL -->
        <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-8 md:p-12 mb-10">
            <div class="flex justify-between items-center mb-10">
                <h2 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter flex items-center">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full mr-3 animate-pulse"></span>
                    Monitor de Equipo en Vivo
                </h2>
                <?php if($pendientes > 0): ?>
                    <a href="index.php?p=gestion_ausencias" class="bg-rose-500 text-white px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-200 animate-bounce">
                        <?php echo $pendientes; ?> Pendientes
                    </a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">
                <?php foreach($estadoPlantilla as $p): 
                    $colorDot = 'bg-slate-200'; $borderColor = 'border-slate-100'; $statusText = 'Fuera';
                    if($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') { $colorDot = 'bg-emerald-500'; $borderColor = 'border-emerald-500'; $statusText = 'Trabajando'; }
                    if($p['ultimo_estado'] == 'pausa') { $colorDot = 'bg-amber-500'; $borderColor = 'border-amber-500'; $statusText = 'En Pausa'; }
                ?>
                <div class="flex flex-col items-center group">
                    <div class="relative mb-4">
                        <div class="w-20 h-20 rounded-[30px] bg-slate-50 border-2 <?php echo $borderColor; ?> flex items-center justify-center font-black text-slate-300 text-2xl shadow-sm group-hover:scale-110 transition-transform duration-300">
                            <?php echo substr($p['nombre'], 0, 1); ?>
                        </div>
                        <div class="absolute -top-1 -right-1 w-5 h-5 <?php echo $colorDot; ?> border-4 border-white rounded-full shadow-md"></div>
                    </div>
                    <p class="text-[11px] font-black text-slate-800 uppercase tracking-tighter text-center"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                    <p class="text-[8px] font-bold text-slate-400 uppercase mt-1 italic"><?php echo $statusText; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <!-- VISTA TRABAJADOR (JUDITH) -->
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- PANEL FICHAJE -->
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
                <div class="mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado Actual: <span class="text-slate-900 border-b-2 border-emerald-500 pb-1"><?php echo $miEstadoActual; ?></span></div>
            </div>

            <!-- BOLSA VACACIONES -->
            <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between relative overflow-hidden">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Bolsa de Vacaciones</p>
                    <p class="text-7xl font-black text-blue-600 tracking-tighter mb-2"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?></p>
                    <p class="text-[10px] font-bold text-slate-300 uppercase italic">Días disponibles para elegir</p>
                </div>
                <a href="index.php?p=solicitudes" class="mt-8 block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-600 transition duration-300">Solicitar días libres</a>
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
        })
        .then(res => res.json())
        .then(data => data.success ? location.reload() : Swal.fire('Error', data.message, 'error'));
    }, err => {
        Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Por favor, activa la ubicación para poder registrar tu jornada en CVTools.' });
    });
}
</script>
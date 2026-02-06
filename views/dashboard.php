<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];

// 1. Datos del Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. ALERTAS Y ESTADOS PARA CARMEN (ADMIN)
$alertasAusencias = [];
$estadoPlantilla = [];
if ($_SESSION['rol'] == 'admin') {
    // Solicitudes pendientes
    $alertasAusencias = $pdo->query("SELECT a.*, u.nombre as empleado FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'pendiente'")->fetchAll();

    // Muro de Presencia: Último estado de cada uno HOY
    $sqlPresencia = "
        SELECT u.id, u.nombre, u.horario, f.tipo as ultimo_estado, f.fecha_hora as ultima_hora
        FROM usuarios u
        LEFT JOIN (
            SELECT f1.usuario_id, f1.tipo, f1.fecha_hora
            FROM fichajes f1
            WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())
        ) f ON u.id = f.usuario_id
        ORDER BY u.nombre ASC";
    $estadoPlantilla = $pdo->query($sqlPresencia)->fetchAll();
}

// 3. Mi Estado Actual
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 4. Próximo festivo
$stmtNext = $pdo->prepare("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
$stmtNext->execute();
$nextFest = $stmtNext->fetch();
?>

<div class="max-w-6xl mx-auto">
    
    <!-- NOTIFICACIONES ADMIN -->
    <?php if(!empty($alertasAusencias)): ?>
        <div class="bg-white border-2 border-rose-500 rounded-[40px] p-6 mb-8 shadow-xl shadow-rose-100 flex flex-col md:flex-row justify-between items-center gap-4 animate-in slide-in-from-top duration-500">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-rose-500 rounded-2xl flex items-center justify-center text-white animate-pulse"><i class="fas fa-bell"></i></div>
                <p class="text-sm font-black text-slate-800 uppercase italic">Tienes <?php echo count($alertasAusencias); ?> solicitudes esperando validación</p>
            </div>
            <a href="index.php?p=gestion_ausencias" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-rose-600 transition">Gestionar</a>
        </div>
    <?php endif; ?>

    <!-- HEADER BIENVENIDA -->
    <div class="bg-slate-900 p-10 rounded-[45px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-4xl font-black italic uppercase tracking-tighter leading-none mb-2">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?></h1>
            <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.3em]">Benigànim • CVTools HQ</p>
        </div>
        <?php if($nextFest): ?>
            <div class="relative z-10 bg-white/10 backdrop-blur-md px-6 py-4 rounded-3xl border border-white/10">
                <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest mb-1 text-center">Festivo a la vista</p>
                <p class="font-black text-sm uppercase"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> — <?php echo $nextFest['nombre']; ?></p>
            </div>
        <?php endif; ?>
        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- FICHAJE -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 text-center">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-10 rounded-[35px] font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-play mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-10 rounded-[35px] font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-coffee mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-10 rounded-[35px] font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
            <div class="mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Tu estado: <span class="text-slate-900 border-b-2 border-emerald-500 pb-1"><?php echo $miEstadoActual; ?></span>
            </div>
        </div>

        <!-- BOLSA VACACIONES -->
        <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">Bolsa Vacacional</h3>
                <p class="text-xs font-bold text-slate-400">Te quedan para elegir:</p>
                <p class="text-7xl font-black text-blue-600 tracking-tighter my-2"><?php echo $user['dias_vacaciones_disponibles']; ?><span class="text-xs ml-2 text-slate-300">días</span></p>
            </div>
            <a href="index.php?p=solicitudes" class="block text-center bg-slate-100 hover:bg-blue-600 hover:text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition duration-300">Solicitar días</a>
        </div>

        <!-- MURO DE PRESENCIA (SOLO CARMEN) -->
        <?php if($_SESSION['rol'] == 'admin'): ?>
        <div class="lg:col-span-3 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10">
            <h3 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-8 flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 animate-pulse"></span>
                Monitor de Equipo en Benigànim
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach($estadoPlantilla as $p): 
                    $colorDot = 'bg-slate-300';
                    $border = 'border-slate-100';
                    if($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') { $colorDot = 'bg-emerald-500'; $border = 'border-emerald-500'; }
                    if($p['ultimo_estado'] == 'pausa') { $colorDot = 'bg-amber-500'; $border = 'border-amber-500'; }
                ?>
                <div class="flex flex-col items-center group">
                    <div class="relative mb-3">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 border-2 <?php echo $border; ?> flex items-center justify-center font-black text-slate-400 text-xl shadow-sm group-hover:scale-110 transition">
                            <?php echo substr($p['nombre'], 0, 1); ?>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $colorDot; ?> border-2 border-white rounded-full shadow-sm"></div>
                    </div>
                    <p class="text-[10px] font-black text-slate-700 uppercase"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                    <p class="text-[8px] font-bold text-slate-400 uppercase italic"><?php echo $p['horario']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
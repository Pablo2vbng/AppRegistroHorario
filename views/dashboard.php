<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];

// 1. Datos del Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. ALERTAS PARA CARMEN (ADMIN)
$alertasAusencias = [];
if ($_SESSION['rol'] == 'admin') {
    // Buscar todas las ausencias pendientes con el nombre del que la pide
    $sqlA = "SELECT a.*, u.nombre as empleado FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'pendiente'";
    $alertasAusencias = $pdo->query($sqlA)->fetchAll();
}

// 3. Estado Fichaje
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 4. Próximo festivo
$stmtNext = $pdo->prepare("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
$stmtNext->execute();
$nextFest = $stmtNext->fetch();
?>

<div class="max-w-6xl mx-auto">

    <!-- SECCIÓN DE NOTIFICACIONES PARA CARMEN -->
    <?php if(!empty($alertasAusencias)): ?>
        <div class="bg-white border-2 border-rose-500 rounded-[40px] p-8 mb-10 shadow-2xl shadow-rose-100 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6 text-center md:text-left">
                <div class="w-16 h-16 bg-rose-500 rounded-2xl flex items-center justify-center text-white text-2xl animate-pulse">
                    <i class="fas fa-bell"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">Tienes solicitudes pendientes</h3>
                    <p class="text-slate-400 font-bold text-sm">Carmen, hay <?php echo count($alertasAusencias); ?> peticiones de vacaciones o bajas esperando tu validación.</p>
                </div>
            </div>
            <a href="index.php?p=gestion_ausencias" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:bg-rose-500 transition duration-300">
                Validar Ahora
            </a>
        </div>
    <?php endif; ?>

    <!-- PANEL BIENVENIDA -->
    <div class="relative overflow-hidden bg-slate-900 p-8 md:p-12 rounded-[40px] text-white shadow-2xl mb-10">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-4xl font-black italic uppercase tracking-tighter">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
                <p class="text-blue-400 font-bold text-xs uppercase tracking-[0.3em] mt-2">Sede Central Benigànim</p>
            </div>
            <?php if($nextFest): ?>
            <div class="bg-white/5 backdrop-blur-md px-8 py-4 rounded-3xl border border-white/10 text-center">
                <p class="text-[10px] font-black text-blue-300 uppercase tracking-widest mb-1">Próximo Festivo</p>
                <p class="font-bold text-sm"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> - <?php echo $nextFest['nombre']; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- REGISTRO JORNADA -->
        <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border border-slate-200 p-8 md:p-12 text-center">
            <h2 class="text-[10px] font-black mb-12 uppercase tracking-[0.4em] text-slate-300">Registro de Jornada</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual != 'fuera' && $miEstadoActual != 'salida') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-play mb-4"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-pause mb-4"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-power-off mb-4"></i> SALIR
                </button>
            </div>
        </div>

        <!-- BOLSA VACACIONES -->
        <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 p-10 flex flex-col relative overflow-hidden">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-10 border-b border-slate-50 pb-5">Bolsa de Vacaciones</h3>
            <div class="space-y-8 relative z-10">
                <div class="flex justify-between items-end">
                    <p class="text-sm font-bold text-slate-400">Total Anual</p>
                    <p class="text-2xl font-black text-slate-800 italic">22 <span class="text-[10px] text-slate-300 uppercase">días</span></p>
                </div>
                <div class="pt-8 border-t border-slate-100 flex justify-between items-center">
                    <p class="text-xs font-black text-slate-900 uppercase italic tracking-tighter">Tus Disponibles</p>
                    <p class="text-6xl font-black text-blue-600 tracking-tighter"><?php echo $user['dias_vacaciones_disponibles']; ?></p>
                </div>
            </div>
            <a href="index.php?p=solicitudes" class="mt-12 block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg">Pedir días</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
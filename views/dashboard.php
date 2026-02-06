<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];

// 1. Datos Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. Notificaciones para Carmen
$pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();

// 3. Estado Fichaje (Optimizado para permitir múltiples jornadas)
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// Lógica de botones: Permitir ENTRAR si nunca fichó hoy O si el último fue SALIDA
$puedeEntrar = ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida');
$puedePausar = ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar');
$puedeSalir = ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar' || $miEstadoActual == 'pausa');
?>

<div class="max-w-6xl mx-auto">
    <!-- AVISO PENDIENTES CARMEN -->
    <?php if($_SESSION['rol'] == 'admin' && $pendientes > 0): ?>
        <div class="bg-rose-50 border-2 border-rose-100 p-6 rounded-[35px] mb-8 flex justify-between items-center shadow-xl shadow-rose-50">
            <p class="text-rose-800 font-black text-xs uppercase italic tracking-tighter"><i class="fas fa-bell mr-3 animate-pulse"></i> Carmen, tienes <?php echo $pendientes; ?> solicitudes por validar</p>
            <a href="index.php?p=gestion_ausencias" class="bg-slate-900 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase">Gestionar</a>
        </div>
    <?php endif; ?>

    <!-- PANEL DE BIENVENIDA -->
    <div class="bg-slate-900 p-10 rounded-[45px] text-white shadow-2xl mb-10 relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="relative z-10">
            <h1 class="text-3xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.3em]">Benigànim • Registro Digital</p>
        </div>
        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- FICHAJE (FIX SEGUNDA JORNADA) -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 text-center">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo !$puedeEntrar ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-play mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo !$puedePausar ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-coffee mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo !$puedeSalir ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[40px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
            <div class="mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado: <span class="text-slate-900"><?php echo $miEstadoActual; ?></span></div>
        </div>

        <!-- BOLSA VACACIONES -->
        <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Bolsa de Vacaciones</p>
                <p class="text-7xl font-black text-blue-600 tracking-tighter"><?php echo $user['dias_vacaciones_disponibles']; ?><span class="text-xs ml-1 text-slate-300">días</span></p>
            </div>
            <a href="index.php?p=solicitudes" class="block text-center bg-slate-50 hover:bg-blue-600 hover:text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition duration-300 shadow-sm border border-slate-100">Pedir Vacaciones</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
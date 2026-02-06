<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}

$pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
?>

<div class="max-w-6xl mx-auto">
    <!-- HEADER -->
    <div class="bg-slate-900 p-10 rounded-[45px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?></h1>
            <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.3em]">Benigànim • Sede Central</p>
        </div>
        <?php if($esAdmin && $pendientes > 0): ?>
            <a href="index.php?p=gestion_ausencias" class="bg-rose-500 px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest animate-bounce shadow-xl">
                Validar Solicitudes (<?php echo $pendientes; ?>)
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <?php if(!$esAdmin): ?>
        <!-- TRABAJADOR -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 text-center">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 text-white py-10 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-play mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 text-white py-12 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-coffee mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 text-white py-12 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
            <p id="gps-status" class="mt-8 text-[9px] font-black text-slate-400 uppercase italic">Registro por GPS habilitado para Benigànim</p>
        </div>
        <?php else: ?>
        <!-- ADMIN -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10">
            <h2 class="text-sm font-black mb-8 uppercase italic tracking-widest text-slate-800 border-b pb-4">Panel de Control RRHH</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="index.php?p=informe_global" class="p-6 bg-slate-50 rounded-[30px] border border-slate-100 hover:bg-emerald-50 transition group">
                    <i class="fas fa-file-invoice text-emerald-500 mb-3 text-xl group-hover:scale-110 transition"></i>
                    <p class="font-black text-slate-800 uppercase text-xs">Informe Global Anual</p>
                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Ver todas las horas de la plantilla</p>
                </a>
                <a href="index.php?p=calendario_anual" class="p-6 bg-slate-50 rounded-[30px] border border-slate-100 hover:bg-blue-50 transition group">
                    <i class="fas fa-calendar-alt text-blue-500 mb-3 text-xl group-hover:scale-110 transition"></i>
                    <p class="font-black text-slate-800 uppercase text-xs">Calendario de Equipo</p>
                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Organizar vacaciones y festivos</p>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- BOLSA VACACIONES (Para Trabajadores) -->
        <?php if(!$esAdmin): ?>
        <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between relative overflow-hidden">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Saldo de Tiempo</p>
                <p class="text-7xl font-black text-blue-600 tracking-tighter mb-2"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?></p>
                <p class="text-[10px] font-bold text-slate-300 uppercase italic">Días disponibles</p>
            </div>
            <div class="mt-6 p-4 bg-blue-50 rounded-2xl border border-blue-100">
                <p class="text-[9px] font-black text-blue-700 uppercase leading-relaxed">
                    <i class="fas fa-magic mr-1"></i> Si trabajas más de 8h, este saldo sube automáticamente.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function fichar(tipo) {
    Swal.fire({ title: 'Obteniendo GPS...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
        })
        .then(res => res.json()).then(data => location.reload());
    }, err => {
        Swal.fire({ icon: 'error', title: 'GPS Obligatorio', text: 'Debes activar la ubicación para fichar.' });
    });
}
</script>
<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. Info para el Admin (Muro de presencia y Alertas)
$pendientes = 0; $estadoPlantilla = [];
if ($esAdmin) {
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.nombre, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol != 'admin' ORDER BY u.nombre ASC")->fetchAll();
}

// 3. Conteo de cierres para el desglose visual
$stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
$diasCierre = $stmtC->fetchColumn();

// 4. Estado Fichaje Trabajador
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}
?>

<div class="max-w-6xl mx-auto pb-10">
    
    <!-- BIENVENIDA -->
    <div class="bg-slate-900 p-10 rounded-[50px] text-white shadow-2xl mb-10 relative overflow-hidden flex flex-col md:flex-row justify-between items-center">
        <div class="relative z-10">
            <h1 class="text-3xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo $user['nombre']; ?></h1>
            <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.4em]">Benigànim • Registro Digital CVTools</p>
        </div>
        <?php if($esAdmin && $pendientes > 0): ?>
            <a href="index.php?p=gestion_ausencias" class="bg-rose-500 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase animate-bounce shadow-xl">Tienes <?php echo $pendientes; ?> solicitudes</a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <?php if(!$esAdmin): ?>
        <!-- PANEL FICHAJE TRABAJADOR -->
        <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center">
            <h2 class="text-[10px] font-black mb-10 uppercase tracking-widest text-slate-300">Control de Jornada (GPS)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 text-white py-12 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-play mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 text-white py-12 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-coffee mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 text-white py-12 rounded-[40px] font-black text-xl shadow-xl active:scale-95 transition flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- MONITOR DE EQUIPO ADMIN -->
        <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10">
            <h2 class="text-sm font-black mb-8 uppercase italic tracking-widest text-slate-800 border-b pb-4">Monitor en tiempo real</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <?php foreach($estadoPlantilla as $p): 
                    $color = ($p['ultimo_estado'] == 'entrada') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-400' : 'bg-slate-200');
                ?>
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-3xl <?php echo $color; ?> flex items-center justify-center text-white font-black text-xl shadow-lg mb-3">
                        <?php echo substr($p['nombre'], 0, 1); ?>
                    </div>
                    <p class="text-[10px] font-black text-slate-700 uppercase"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- BOLSA VACACIONES -->
        <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Bolsa Vacacional</p>
                <div class="space-y-4 mb-10">
                    <div class="flex justify-between text-xs font-bold text-slate-400"><span>Total Anual:</span> <span>22 días</span></div>
                    <div class="flex justify-between text-xs font-bold text-rose-400 border-b border-rose-50 pb-2"><span>Cierres Empresa:</span> <span>- <?php echo $diasCierre; ?> días</span></div>
                    <div class="flex justify-between items-end pt-2">
                        <span class="text-[10px] font-black uppercase text-slate-800 italic">Disponibles:</span>
                        <span class="text-6xl font-black text-blue-600 tracking-tighter leading-none"><?php echo round($user['dias_vacaciones_disponibles'], 1); ?></span>
                    </div>
                </div>
            </div>
            <a href="index.php?p=solicitudes" class="block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-600 transition">Solicitar días</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    Swal.fire({ title: 'Localizando GPS...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}&lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
        })
        .then(res => res.json()).then(data => location.reload());
    }, err => {
        Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Activa la ubicación para fichar en Benigànim.' });
    });
}
</script>
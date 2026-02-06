<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. Datos Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. ALERTAS Y MURO DE PRESENCIA (Solo Admin)
$pendientes = 0; $estadoPlantilla = [];
if ($esAdmin) {
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.nombre, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol != 'admin' ORDER BY u.nombre ASC")->fetchAll();
}

// 3. Estado Fichaje (Solo para trabajadores)
$miEstadoActual = 'fuera';
if (!$esAdmin) {
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]);
    $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}
?>

<div class="max-w-6xl mx-auto">
    
    <!-- HEADER BIENVENIDA -->
    <div class="bg-slate-900 p-10 rounded-[45px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?></h1>
            <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.3em] italic">Benigànim • Sede Central</p>
        </div>
        <?php if($esAdmin && $pendientes > 0): ?>
            <a href="index.php?p=gestion_ausencias" class="relative z-10 bg-rose-500 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest animate-bounce shadow-xl">
                ¡Tienes <?php echo $pendientes; ?> solicitudes!
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <?php if(!$esAdmin): ?>
        <!-- PANEL DE FICHAJE CON GPS (Solo Trabajadores) -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 text-center">
            <h2 class="text-[10px] font-black mb-10 uppercase tracking-[0.4em] text-slate-300 italic">Validación por GPS Activa</h2>
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
            <p id="gps-status" class="mt-8 text-[9px] font-black text-slate-400 uppercase italic">Localización: Pendiente de pulsar botón...</p>
        </div>
        <?php else: ?>
        <!-- PANEL VISTA RÁPIDA (Solo Admin) -->
        <div class="lg:col-span-2 bg-white rounded-[45px] shadow-sm border border-slate-200 p-10">
            <h2 class="text-sm font-black mb-8 uppercase italic tracking-widest text-slate-800">Estado de Plantilla ahora</h2>
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

        <!-- BOLSA VACACIONES (Para todos) -->
        <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 p-10 flex flex-col justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-10">Bolsa Vacacional</p>
                <p class="text-7xl font-black text-blue-600 tracking-tighter"><?php echo $user['dias_vacaciones_disponibles']; ?><span class="text-xs ml-1 text-slate-300">días</span></p>
            </div>
            <a href="index.php?p=solicitudes" class="block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-600 transition">Gestionar mis días</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    const statusText = document.getElementById('gps-status');
    if (statusText) statusText.innerText = "Obteniendo ubicación GPS...";

    navigator.geolocation.getCurrentPosition(position => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        if (statusText) statusText.innerText = `Ubicación detectada. Registrando ${tipo}...`;

        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}&lat=${lat}&lng=${lng}`
        })
        .then(res => res.json())
        .then(data => data.success ? location.reload() : alert(data.message));
    }, error => {
        alert("Error: Para fichar en CVTools es obligatorio activar el GPS en tu móvil.");
        if (statusText) statusText.innerText = "Error GPS: Permiso denegado.";
    });
}
</script>
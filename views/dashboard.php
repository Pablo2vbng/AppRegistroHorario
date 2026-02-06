<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];

// 1. Datos del Usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. Calcular días de empresa (cierres)
$stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
$diasEmpresa = $stmtC->fetchColumn();

// 3. Estado Fichaje
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 4. Próximo festivo en Benigànim
$stmtNext = $pdo->prepare("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
$stmtNext->execute();
$nextFest = $stmtNext->fetch();
?>

<div class="max-w-6xl mx-auto">
    <!-- PANEL BIENVENIDA -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 bg-slate-900 p-8 rounded-[40px] text-white shadow-2xl">
        <div>
            <h1 class="text-3xl font-black italic uppercase tracking-tighter">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1>
            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">Sede Central Benigànim</p>
        </div>
        <?php if($nextFest): ?>
        <div class="bg-white/10 px-6 py-3 rounded-2xl border border-white/10 text-center">
            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Próximo Festivo</p>
            <p class="font-bold text-sm"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> - <?php echo $nextFest['nombre']; ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- FICHAJE -->
        <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border p-8 md:p-12 text-center">
            <h2 class="text-xs font-black mb-10 uppercase tracking-[0.2em] text-slate-300">Registro de Jornada</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual != 'fuera' && $miEstadoActual != 'salida') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-emerald-500 hover:bg-emerald-600 text-white py-10 rounded-[32px] font-black text-2xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-play mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-amber-500 hover:bg-amber-600 text-white py-10 rounded-[32px] font-black text-2xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-pause mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-rose-500 hover:bg-rose-600 text-white py-10 rounded-[32px] font-black text-2xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
        </div>

        <!-- RESUMEN VACACIONES DETALLADO -->
        <div class="bg-white rounded-[40px] shadow-sm border p-10 flex flex-col justify-center">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 border-b pb-4">Bolsa de Vacaciones</h3>
            
            <div class="space-y-6">
                <div class="flex justify-between items-end">
                    <p class="text-sm font-bold text-slate-500">Total Anual</p>
                    <p class="text-2xl font-black text-slate-800">22 <span class="text-[10px] text-slate-300">días</span></p>
                </div>
                <div class="flex justify-between items-end">
                    <p class="text-sm font-bold text-rose-400">Cierre Empresa</p>
                    <p class="text-lg font-black text-rose-500">- <?php echo $diasEmpresa; ?> <span class="text-[10px] opacity-50">días</span></p>
                </div>
                <div class="pt-6 border-t border-slate-100 flex justify-between items-end">
                    <p class="text-sm font-black text-slate-800 uppercase italic">Disponibles</p>
                    <p class="text-4xl font-black text-blue-600"><?php echo $user['dias_vacaciones_disponibles']; ?></p>
                </div>
            </div>
            
            <a href="index.php?p=solicitudes" class="mt-10 block text-center bg-slate-50 text-slate-400 hover:text-blue-600 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition">Pedir mis días libremente</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
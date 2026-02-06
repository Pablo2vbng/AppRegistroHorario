<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];

// 1. Datos del Usuario actualizados
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. Contar cierres de empresa reales en la DB
$stmtC = $pdo->query("SELECT COUNT(*) FROM festivos WHERE descuenta_vacaciones = 1");
$diasCierreEmpresa = $stmtC->fetchColumn();

// 3. Calcular disponibles reales (esto es lo que fallaba)
$disponiblesReales = $user['dias_vacaciones_disponibles'];

// 4. Estado Fichaje
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 5. Próximo festivo
$stmtNext = $pdo->prepare("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1");
$stmtNext->execute();
$nextFest = $stmtNext->fetch();
?>

<div class="max-w-6xl mx-auto">
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
        <!-- Decoración fondo -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- REGISTRO JORNADA -->
        <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border border-slate-200 p-8 md:p-12 text-center">
            <h2 class="text-[10px] font-black mb-12 uppercase tracking-[0.4em] text-slate-300">Registro de Jornada</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual != 'fuera' && $miEstadoActual != 'salida') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                    <i class="fas fa-play mb-4 group-hover:scale-110 transition"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                    <i class="fas fa-pause mb-4 group-hover:scale-110 transition"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[35px] font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center group">
                    <i class="fas fa-power-off mb-4 group-hover:scale-110 transition"></i> SALIR
                </button>
            </div>
            <div class="mt-10 inline-flex items-center px-4 py-2 bg-slate-50 rounded-full border border-slate-100">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-3 animate-pulse"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado Actual: <span class="text-slate-900"><?php echo $miEstadoActual; ?></span></span>
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
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-sm font-bold text-rose-400 leading-none">Cierre Empresa</p>
                        <p class="text-[9px] text-slate-300 font-bold uppercase mt-1">Días forzosos</p>
                    </div>
                    <p class="text-xl font-black text-rose-500">- <?php echo $diasCierreEmpresa; ?> <span class="text-[10px] opacity-50 uppercase">días</span></p>
                </div>
                <div class="pt-8 border-t border-slate-100 flex justify-between items-center">
                    <p class="text-xs font-black text-slate-900 uppercase italic tracking-tighter">Disponibles</p>
                    <p class="text-6xl font-black text-blue-600 tracking-tighter"><?php echo $disponiblesReales; ?></p>
                </div>
            </div>
            
            <a href="index.php?p=solicitudes" class="mt-12 block text-center bg-slate-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg hover:bg-blue-600 transition">Pedir días libres</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}
</script>
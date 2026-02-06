<?php
require_once 'config/config.php';

// 1. Datos Generales
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM usuarios");
$totalEmp = $stmtTotal->fetchColumn();

// 2. Mi Estado Actual
$miId = $_SESSION['usuario_id'];
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 3. ALERTAS PARA CARMEN (ADMIN)
$alertas = [];
if ($_SESSION['rol'] == 'admin') {
    // Buscar gente que fichó entrada hace más de 9 horas y no ha salido
    $sqlAlertas = "
        SELECT u.nombre, f.fecha_hora 
        FROM fichajes f 
        JOIN usuarios u ON f.usuario_id = u.id 
        WHERE f.tipo = 'entrada' 
        AND DATE(f.fecha_hora) = CURDATE()
        AND f.id = (SELECT MAX(id) FROM fichajes WHERE usuario_id = u.id AND DATE(fecha_hora) = CURDATE())
        AND TIMESTAMPDIFF(HOUR, f.fecha_hora, NOW()) >= 9
    ";
    $alertas = $pdo->query($sqlAlertas)->fetchAll();
}
?>

<div class="max-w-5xl mx-auto">
    <!-- SECCIÓN ALERTAS (Solo Admin) -->
    <?php if(!empty($alertas)): ?>
        <div class="bg-rose-50 border-2 border-rose-200 p-6 rounded-3xl mb-8 animate-pulse">
            <h3 class="text-rose-800 font-black text-xs uppercase mb-3"><i class="fas fa-exclamation-triangle mr-2"></i> Alertas de Jornada Excesiva</h3>
            <ul class="space-y-2">
                <?php foreach($alertas as $alt): ?>
                    <li class="text-rose-700 text-sm font-bold flex justify-between">
                        <span><?php echo $alt['nombre']; ?> lleva más de 9h sin fichar salida.</span>
                        <a href="index.php?p=informes_equipo" class="underline uppercase text-[10px]">Revisar</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- FICHAJE -->
    <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 p-8 md:p-16 text-center mb-10">
        <h1 class="text-3xl font-black text-slate-800 mb-10 uppercase italic tracking-tighter">Control de Acceso</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-emerald-500 hover:bg-emerald-600 text-white py-8 rounded-3xl font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                <i class="fas fa-play-circle mb-3 text-3xl"></i> ENTRAR
            </button>
            <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-amber-500 hover:bg-amber-600 text-white py-8 rounded-3xl font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                <i class="fas fa-pause-circle mb-3 text-3xl"></i> PAUSA
            </button>
            <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-rose-500 hover:bg-rose-600 text-white py-8 rounded-3xl font-black text-2xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                <i class="fas fa-stop-circle mb-3 text-3xl"></i> SALIR
            </button>
        </div>
        
        <div class="mt-10 inline-block px-6 py-2 bg-slate-100 rounded-full text-slate-500 font-bold text-xs uppercase tracking-widest">
            Tu estado: <span class="text-slate-800"><?php echo $miEstadoActual; ?></span>
        </div>
    </div>

    <!-- CARDS INFO -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Días de Vacaciones</p>
                <p class="text-3xl font-black text-slate-800">22</p>
            </div>
            <i class="fas fa-umbrella-beach text-blue-100 text-5xl"></i>
        </div>
        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Equipo CVTools</p>
                <p class="text-3xl font-black text-slate-800"><?php echo $totalEmp; ?> Personas</p>
            </div>
            <i class="fas fa-users text-slate-100 text-5xl"></i>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json())
    .then(data => data.success ? location.reload() : alert(data.message));
}
</script>
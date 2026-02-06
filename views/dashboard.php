<?php
require_once 'config/config.php';

// 1. Estado del usuario logueado
$miId = $_SESSION['usuario_id'];
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

// 2. Datos para el Administrador (Carmen)
$alertas = [];
$statsHoy = ['trabajando' => 0, 'total' => 0];

if ($_SESSION['rol'] == 'admin') {
    // Alertas > 9h
    $sqlAlertas = "SELECT u.nombre FROM fichajes f JOIN usuarios u ON f.usuario_id = u.id 
                   WHERE f.tipo = 'entrada' AND DATE(f.fecha_hora) = CURDATE()
                   AND f.id = (SELECT MAX(id) FROM fichajes WHERE usuario_id = u.id AND DATE(fecha_hora) = CURDATE())
                   AND TIMESTAMPDIFF(HOUR, f.fecha_hora, NOW()) >= 9";
    $alertas = $pdo->query($sqlAlertas)->fetchAll();

    // Gráfico de actividad última semana
    $sqlGrafico = "SELECT DATE(fecha_hora) as dia, COUNT(*) as registros FROM fichajes 
                   WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY dia ORDER BY dia ASC";
    $datosGrafico = $pdo->query($sqlGrafico)->fetchAll(PDO::FETCH_ASSOC);
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-6xl mx-auto">
    
    <!-- HEADER BIENVENIDA -->
    <div class="mb-10">
        <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Sede Benigànim</h1>
        <p class="text-slate-500 font-bold">Bienvenido/a al registro horario de CVTools</p>
    </div>

    <?php if(!empty($alertas)): ?>
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-8 rounded-r-xl animate-pulse">
            <p class="text-rose-800 text-xs font-black uppercase"><i class="fas fa-exclamation-circle mr-2"></i> Atención Carmen: Trabajadores excediendo jornada</p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- FICHAJE (Para todos los trabajadores) -->
        <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border p-8 md:p-12 text-center">
            <h2 class="text-xl font-black mb-10 uppercase tracking-widest text-slate-400">Control de Jornada</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button onclick="fichar('entrada')" <?php echo ($miEstadoActual != 'fuera' && $miEstadoActual != 'salida') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-emerald-500 hover:bg-emerald-600 text-white py-8 rounded-3xl font-black text-xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-sign-in-alt mb-3"></i> ENTRAR
                </button>
                <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-amber-500 hover:bg-amber-600 text-white py-8 rounded-3xl font-black text-xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-coffee mb-3"></i> PAUSA
                </button>
                <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                    class="disabled:opacity-20 bg-rose-500 hover:bg-rose-600 text-white py-8 rounded-3xl font-black text-xl shadow-xl transition active:scale-95 flex flex-col items-center">
                    <i class="fas fa-power-off mb-3"></i> SALIR
                </button>
            </div>
            <p class="mt-8 font-black text-xs uppercase tracking-tighter text-slate-300 italic">Tu estado actual en Benigànim: <span class="text-slate-900"><?php echo strtoupper($miEstadoActual); ?></span></p>
        </div>

        <!-- GRÁFICO (Solo Carmen) -->
        <div class="bg-white rounded-[40px] shadow-sm border p-8">
            <?php if($_SESSION['rol'] == 'admin'): ?>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6 text-center">Actividad Semanal</h3>
                <canvas id="chartActividad"></canvas>
            <?php else: ?>
                <div class="text-center py-10">
                    <i class="fas fa-clock text-slate-100 text-6xl mb-4"></i>
                    <p class="text-slate-400 font-bold uppercase text-[10px]">Recuerda registrar siempre tus pausas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(res => res.json()).then(data => data.success ? location.reload() : alert(data.message));
}

<?php if($_SESSION['rol'] == 'admin'): ?>
new Chart(document.getElementById('chartActividad'), {
    type: 'line',
    data: {
        labels: [<?php foreach($datosGrafico as $d) echo "'" . date('d/m', strtotime($d['dia'])) . "',"; ?>],
        datasets: [{
            label: 'Registros',
            data: [<?php foreach($datosGrafico as $d) echo $d['registros'] . ","; ?>],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, display: false }, x: { grid: { display: false } } } }
});
<?php endif; ?>
</script>
<?php
// Datos para el dashboard (ya los tienes de antes)
$miId = $_SESSION['usuario_id'];
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';

$stmtTotal = $pdo->query("SELECT COUNT(*) FROM usuarios");
$totalEmp = $stmtTotal->fetchColumn();
?>

<div class="max-w-5xl mx-auto">
    <!-- PANEL DE FICHAJE -->
    <div class="bg-white rounded-3xl shadow-sm border p-6 md:p-10 mb-8 text-center">
        <h2 class="text-xl md:text-2xl font-black text-slate-800 mb-8 uppercase italic tracking-tighter">Registro Horario Digital</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-emerald-500 text-white py-6 rounded-2xl font-black text-xl shadow-lg active:scale-95 transition">
                <i class="fas fa-play mb-2 block"></i> ENTRAR
            </button>
            <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-amber-500 text-white py-6 rounded-2xl font-black text-xl shadow-lg active:scale-95 transition">
                <i class="fas fa-pause mb-2 block"></i> PAUSA
            </button>
            <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                class="disabled:opacity-20 bg-rose-500 text-white py-6 rounded-2xl font-black text-xl shadow-lg active:scale-95 transition">
                <i class="fas fa-stop mb-2 block"></i> SALIR
            </button>
        </div>
        <p class="mt-6 text-slate-400 font-bold text-sm uppercase tracking-widest">Estado: <span class="text-slate-800"><?php echo $miEstadoActual; ?></span></p>
    </div>

    <!-- CARDS MÓVILES -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-2xl border-l-4 border-emerald-500 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1 tracking-widest">En su puesto</p>
            <p class="text-3xl font-black text-slate-800">1</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1 tracking-widest">Plantilla</p>
            <p class="text-3xl font-black text-slate-800"><?php echo $totalEmp; ?></p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-rose-400 shadow-sm flex items-center justify-between">
            <a href="https://wa.me/34600000000" class="text-emerald-600 font-black text-xs uppercase"><i class="fab fa-whatsapp mr-2"></i> Notificar Tardanza</a>
        </div>
    </div>
</div>

<script>
function fichar(tipo) {
    fetch('api/fichar.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `tipo=${tipo}` })
    .then(() => location.reload());
}
</script>
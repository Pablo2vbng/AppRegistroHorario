<?php
require_once 'config/config.php';

// 1. Obtener Total de Empleados
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM usuarios");
$totalEmpleados = $stmtTotal->fetchColumn();

// 2. Obtener estado actual de todos los empleados HOY
$sqlEstadoHoy = "
    SELECT u.id, u.nombre, u.foto_url, f.tipo as ultimo_estado
    FROM usuarios u
    LEFT JOIN (
        SELECT f1.usuario_id, f1.tipo
        FROM fichajes f1
        WHERE f1.id = (
            SELECT MAX(f2.id) 
            FROM fichajes f2 
            WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE()
        )
    ) f ON u.id = f.usuario_id
";
$stmtEstados = $pdo->query($sqlEstadoHoy);
$empleados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

$trabajandoArr = [];
$noFichadoCount = 0;

foreach ($empleados as $emp) {
    if ($emp['ultimo_estado'] == 'entrada' || $emp['ultimo_estado'] == 'reanudar') {
        $trabajandoArr[] = $emp;
    } elseif (is_null($emp['ultimo_estado']) || $emp['ultimo_estado'] == 'salida') {
        $noFichadoCount++;
    }
}

// 3. Estado del usuario logueado
$miId = $_SESSION['usuario_id'];
$stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmtMiEstado->execute([$miId]);
$miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans">
    <div class="flex flex-col md:flex-row min-h-screen">
        
        <!-- SIDEBAR ACTUALIZADO CON ENLACES ?p= -->
        <div class="bg-slate-900 shadow-xl w-full md:w-64 flex-shrink-0 text-slate-300">
            <div class="p-6 text-center border-b border-slate-800">
                <img src="assets/img/logoCvTools.jpg" alt="CVTools" class="w-32 mx-auto rounded shadow-lg">
            </div>
           <!-- Busca el bloque <nav> en dashboard.php y sustitúyelo por este -->
<nav class="text-sm font-semibold pt-4">
    <a href="index.php?p=dashboard" class="flex items-center text-white py-4 pl-6 bg-slate-800 border-l-4 border-blue-500">
        <i class="fas fa-desktop mr-3"></i> Escritorio
    </a>
    
    <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest border-t border-slate-800">Mi Espacio</p>
    <a href="index.php?p=jornada" class="flex items-center hover:text-white opacity-75 hover:opacity-100 py-3 pl-6 transition">
        <i class="fas fa-history mr-3"></i> Mi Jornada
    </a>
    <!-- NUEVO: Ahora todos pueden ir a pedir vacaciones -->
    <a href="index.php?p=solicitudes" class="flex items-center hover:text-white opacity-75 hover:opacity-100 py-3 pl-6 transition">
        <i class="fas fa-calendar-plus mr-3"></i> Vacaciones / Bajas
    </a>
    
    <?php if($_SESSION['rol'] == 'admin'): ?>
    <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest border-t border-slate-800">Administración</p>
    <a href="index.php?p=empleados" class="flex items-center hover:text-white opacity-75 hover:opacity-100 py-3 pl-6 transition">
        <i class="fas fa-users-cog mr-3"></i> Plantilla
    </a>
    <!-- NUEVO: El Admin gestiona las de todos -->
    <a href="index.php?p=gestion_ausencias" class="flex items-center hover:text-white opacity-75 hover:opacity-100 py-3 pl-6 transition text-amber-400">
        <i class="fas fa-tasks mr-3"></i> Gestionar Ausencias
    </a>
    <?php endif; ?>

    <div class="mt-10 px-4">
        <a href="https://wa.me/34600000000?text=Hola,%20notifico%20una%20tardanza" target="_blank" 
           class="flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-lg transition shadow-md">
            <i class="fab fa-whatsapp mr-2 text-lg"></i> Notificar Tardanza
        </a>
    </div>
</nav>
        </div>

        <!-- CONTENIDO -->
        <div class="flex-1 flex flex-col">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center border-b border-slate-200">
                <div class="text-xl font-bold text-slate-800">Hola, <?php echo explode(' ', $_SESSION['nombre'])[0]; ?> 👋</div>
                <div class="flex items-center space-x-6">
                    <div class="hidden md:block text-right">
                        <p class="text-xs text-slate-400 uppercase font-bold tracking-tighter">Fecha y Hora</p>
                        <p class="text-sm font-bold text-slate-600"><?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <a href="logout.php" class="bg-rose-50 text-rose-600 px-4 py-2 rounded-lg font-bold hover:bg-rose-100 transition border border-rose-100">Salir</a>
                </div>
            </header>

            <main class="p-6 lg:p-10 max-w-7xl mx-auto w-full">
                <!-- FICHADOR CENTRAL -->
                <div class="bg-white rounded-2xl shadow-sm p-10 text-center mb-10 border border-slate-200">
                    <h2 class="text-2xl font-black mb-8 text-slate-800 uppercase tracking-tight">Registro Horario Digital</h2>
                    
                    <div class="flex flex-wrap justify-center gap-6">
                        <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-20 bg-emerald-500 hover:bg-emerald-600 text-white w-40 py-5 rounded-2xl font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                            <i class="fas fa-sign-in-alt mb-2"></i> ENTRAR
                        </button>
                        <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-20 bg-amber-500 hover:bg-amber-600 text-white w-40 py-5 rounded-2xl font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                            <i class="fas fa-coffee mb-2"></i> PAUSA
                        </button>
                        <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-20 bg-rose-500 hover:bg-rose-600 text-white w-40 py-5 rounded-2xl font-black text-xl shadow-xl transition transform active:scale-95 flex flex-col items-center">
                            <i class="fas fa-power-off mb-2"></i> SALIR
                        </button>
                    </div>
                    
                    <div class="mt-8">
                        <span class="px-4 py-2 bg-slate-100 rounded-full text-slate-500 text-sm font-bold">
                            Estado actual: <span class="text-slate-800"><?php echo strtoupper($miEstadoActual); ?></span>
                        </span>
                    </div>
                    <div id="status-msg" class="mt-4 h-6 font-bold text-blue-600"></div>
                </div>

                <!-- CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                    <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-emerald-500 relative overflow-hidden">
                        <p class="text-slate-400 text-xs uppercase font-black mb-1">En el puesto</p>
                        <p class="text-5xl font-black text-slate-800"><?php echo count($trabajandoArr); ?></p>
                        <i class="fas fa-user-check absolute -right-4 -bottom-4 text-6xl text-slate-50 opacity-50"></i>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-rose-400">
                        <p class="text-slate-400 text-xs uppercase font-black mb-1">Fuera / Pausa</p>
                        <p class="text-5xl font-black text-slate-800"><?php echo $noFichadoCount; ?></p>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-blue-500">
                        <p class="text-slate-400 text-xs uppercase font-black mb-1">Total Plantilla</p>
                        <p class="text-5xl font-black text-slate-800"><?php echo $totalEmpleados; ?></p>
                    </div>
                </div>

                <!-- EMPLEADOS TRABAJANDO -->
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-slate-200">
                    <h3 class="text-slate-800 font-black mb-8 flex items-center uppercase text-sm tracking-widest">
                        <span class="flex h-3 w-3 mr-3">
                          <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        Equipo en Activo
                    </h3>
                    <div class="flex flex-wrap gap-8">
                        <?php if (empty($trabajandoArr)): ?>
                            <p class="text-slate-400 text-sm italic">No hay actividad registrada en este momento.</p>
                        <?php else: ?>
                            <?php foreach ($trabajandoArr as $trabajador): ?>
                                <div class="flex flex-col items-center group cursor-help">
                                    <div class="relative">
                                        <img src="<?php echo $trabajador['foto_url'] ?: 'https://ui-avatars.com/api/?background=e2e8f0&color=475569&bold=true&name='.urlencode($trabajador['nombre']); ?>" 
                                             class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-md group-hover:scale-105 transition">
                                        <div class="absolute -top-2 -right-2 bg-emerald-500 text-white rounded-full p-1 border-2 border-white shadow-sm">
                                            <i class="fas fa-check text-[10px]"></i>
                                        </div>
                                    </div>
                                    <span class="text-xs mt-3 font-black text-slate-700 uppercase tracking-tighter"><?php echo explode(' ', $trabajador['nombre'])[0]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    function fichar(tipo) {
        const msgDiv = document.getElementById('status-msg');
        msgDiv.innerText = "Sincronizando con el servidor...";
        
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                msgDiv.className = "mt-4 font-bold text-rose-600";
                msgDiv.innerText = "Error: " + data.message;
            }
        });
    }
    </script>
</body>
</html>
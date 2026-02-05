<?php
require_once 'config/config.php';

// 1. Obtener Total de Empleados
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM usuarios");
$totalEmpleados = $stmtTotal->fetchColumn();

// 2. Obtener estado actual de todos los empleados HOY
// Esta consulta es "mágica": busca el último fichaje de cada usuario hoy
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

// 3. Clasificar empleados para las "Cards"
$trabajandoArr = [];
$enPausaCount = 0;
$noFichadoCount = 0;

foreach ($empleados as $emp) {
    if ($emp['ultimo_estado'] == 'entrada' || $emp['ultimo_estado'] == 'reanudar') {
        $trabajandoArr[] = $emp;
    } elseif ($emp['ultimo_estado'] == 'pausa') {
        $enPausaCount++;
    } elseif (is_null($emp['ultimo_estado'])) {
        $noFichadoCount++;
    }
}

// 4. Saber el estado del usuario logueado (para bloquear botones)
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
        <!-- Sidebar -->
        <div class="bg-slate-800 shadow-xl w-full md:w-64 flex-shrink-0">
            <div class="p-6 text-center border-b border-slate-700">
                <img src="assets/img/logoCvTools.jpg" alt="CVTools" class="w-32 mx-auto rounded">
            </div>
            <nav class="text-white text-sm font-semibold pt-3">
                <a href="index.php" class="flex items-center text-white py-4 pl-6 bg-slate-700 border-l-4 border-blue-400">
                    <i class="fas fa-tachometer-alt mr-3"></i> Escritorio
                </a>
                <p class="pl-6 pt-4 pb-2 text-gray-400 text-xs uppercase">Informes</p>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6"><i class="fas fa-clock mr-3"></i> Jornada</a>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6"><i class="fas fa-calendar-alt mr-3"></i> Vacaciones</a>
            </nav>
        </div>

        <div class="flex-1">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center border-b">
                <div class="text-xl font-bold text-slate-700">Hola <?php echo $_SESSION['nombre']; ?></div>
                <div class="flex items-center space-x-4">
                    <span class="text-slate-500 font-medium"><?php echo date('d/m/Y H:i'); ?></span>
                    <a href="logout.php" class="bg-red-50 text-red-600 px-3 py-1 rounded-md font-bold hover:bg-red-100 transition">Salir</a>
                </div>
            </header>

            <main class="p-6">
                <!-- ACCIONES DINÁMICAS -->
                <div class="bg-white rounded-xl shadow-sm p-8 text-center mb-8 border border-slate-200">
                    <h2 class="text-xl font-bold mb-6 text-slate-800">¿Qué vas a hacer ahora?</h2>
                    <div class="flex flex-wrap justify-center gap-4">
                        <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada' || $miEstadoActual == 'reanudar') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-30 bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg transition active:scale-95">
                            ENTRAR
                        </button>
                        <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada' && $miEstadoActual != 'reanudar') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-30 bg-amber-500 hover:bg-amber-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg transition active:scale-95">
                            PAUSA
                        </button>
                        <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'salida' || $miEstadoActual == 'fuera') ? 'disabled' : ''; ?> 
                                class="disabled:opacity-30 bg-rose-500 hover:bg-rose-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg transition active:scale-95">
                            SALIR
                        </button>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Estado actual: <span class="font-bold uppercase"><?php echo $miEstadoActual; ?></span></p>
                    <div id="status-msg" class="mt-2 h-6"></div>
                </div>

                <!-- CARDS DINÁMICAS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-emerald-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-slate-400 text-xs uppercase font-bold">Trabajando</p>
                                <p class="text-4xl font-black text-slate-800"><?php echo count($trabajandoArr); ?></p>
                            </div>
                            <i class="fas fa-door-open text-emerald-100 text-4xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-rose-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-slate-400 text-xs uppercase font-bold">Sin fichar hoy</p>
                                <p class="text-4xl font-black text-slate-800"><?php echo $noFichadoCount; ?></p>
                            </div>
                            <i class="fas fa-user-clock text-rose-100 text-4xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-slate-400 text-xs uppercase font-bold">Total Plantilla</p>
                                <p class="text-4xl font-black text-slate-800"><?php echo $totalEmpleados; ?></p>
                            </div>
                            <i class="fas fa-users text-blue-100 text-4xl"></i>
                        </div>
                    </div>
                </div>

                <!-- LISTA DE EMPLEADOS TRABAJANDO -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                    <h3 class="text-slate-700 font-bold mb-6 flex items-center">
                        <span class="w-3 h-3 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                        PERSONAL EN ACTIVO AHORA
                    </h3>
                    <div class="flex flex-wrap gap-6">
                        <?php if (empty($trabajandoArr)): ?>
                            <p class="text-slate-400 text-sm italic">No hay nadie trabajando en este momento.</p>
                        <?php else: ?>
                            <?php foreach ($trabajandoArr as $trabajador): ?>
                                <div class="flex flex-col items-center group">
                                    <div class="relative">
                                        <img src="<?php echo $trabajador['foto_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($trabajador['nombre']); ?>" 
                                             class="w-16 h-16 rounded-full border-2 border-emerald-500 p-1 shadow-sm group-hover:scale-110 transition">
                                        <span class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></span>
                                    </div>
                                    <span class="text-xs mt-2 font-bold text-slate-600"><?php echo explode(' ', $trabajador['nombre'])[0]; ?></span>
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
        msgDiv.className = "mt-2 font-bold text-blue-600";
        msgDiv.innerText = "Procesando registro...";
        
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload(); // Recarga para ver los nuevos números
            } else {
                msgDiv.className = "mt-2 font-bold text-red-600";
                msgDiv.innerText = "Error: " + data.message;
            }
        });
    }
    </script>
</body>
</html>
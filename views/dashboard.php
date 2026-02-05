<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar -->
        <div class="bg-slate-800 shadow-xl w-full md:w-64">
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
                <p class="pl-6 pt-4 pb-2 text-gray-400 text-xs uppercase">RRHH</p>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6"><i class="fas fa-users mr-3"></i> Empleados</a>
            </nav>
        </div>

        <div class="w-full">
            <header class="bg-white shadow p-4 flex justify-between items-center">
                <div class="text-xl font-semibold text-slate-700">Hola <?php echo $_SESSION['nombre']; ?>, ¡Bienvenido!</div>
                <div class="flex items-center">
                    <span class="mr-4 text-gray-500"><?php echo date('d/m/Y H:i'); ?></span>
                    <a href="logout.php" class="text-red-500 font-bold hover:underline">Cerrar Sesión</a>
                </div>
            </header>

            <main class="p-6">
                <!-- Acciones de Fichaje -->
                <div class="bg-white rounded-lg shadow-sm p-8 text-center mb-8 border border-gray-200">
                    <h2 class="text-2xl font-bold mb-6 text-slate-800 tracking-tight">Registro de Jornada en Tiempo Real</h2>
                    <div class="flex justify-center space-x-6">
                        <button onclick="fichar('entrada')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-10 py-4 rounded-xl font-black text-xl flex items-center shadow-lg transition transform active:scale-95">
                            <i class="fas fa-play-circle mr-3"></i> ENTRAR
                        </button>
                        <button onclick="fichar('pausa')" class="bg-amber-500 hover:bg-amber-600 text-white px-10 py-4 rounded-xl font-black text-xl flex items-center shadow-lg transition transform active:scale-95">
                            <i class="fas fa-pause-circle mr-3"></i> PAUSA
                        </button>
                        <button onclick="fichar('salida')" class="bg-rose-500 hover:bg-rose-600 text-white px-10 py-4 rounded-xl font-black text-xl flex items-center shadow-lg transition transform active:scale-95">
                            <i class="fas fa-stop-circle mr-3"></i> SALIR
                        </button>
                    </div>
                    <div id="status-msg" class="mt-4 font-bold text-gray-600"></div>
                </div>

                <!-- Tarjetas de Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-emerald-500">
                        <p class="text-gray-400 text-xs uppercase font-bold">Trabajando</p>
                        <p class="text-4xl font-black text-slate-800">1</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-rose-500">
                        <p class="text-gray-400 text-xs uppercase font-bold">En Pausa / Fuera</p>
                        <p class="text-4xl font-black text-slate-800">0</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                        <p class="text-gray-400 text-xs uppercase font-bold">Días Vacaciones</p>
                        <p class="text-4xl font-black text-slate-800">22</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Lógica JavaScript -->
    <script>
    function fichar(tipo) {
        const msgDiv = document.getElementById('status-msg');
        msgDiv.innerText = "Registrando...";
        
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `tipo=${tipo}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                msgDiv.innerHTML = `<span class="text-emerald-600">✓ ${data.message}</span>`;
                setTimeout(() => location.reload(), 1500); // Recargamos para ver cambios
            } else {
                msgDiv.innerHTML = `<span class="text-rose-600">Error: ${data.message}</span>`;
            }
        });
    }
    </script>
</body>
</html>
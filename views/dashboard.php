<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <div class="flex flex-col md:flex-row">
        <!-- Sidebar lateral (Estilo Logroot) -->
        <div class="bg-slate-800 shadow-xl h-16 fixed bottom-0 md:relative md:h-screen z-10 w-full md:w-64">
            <div class="p-6">
                <h1 class="text-white text-2xl font-bold">LOGROOT</h1>
            </div>
            <nav class="text-white text-sm font-semibold pt-3">
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-4 pl-6 nav-item bg-slate-700">
                    <i class="fas fa-tachometer-alt mr-3"></i> Escritorio
                </a>
                <p class="pl-6 pt-4 pb-2 text-gray-400 text-xs uppercase">Informes</p>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6 nav-item">
                    <i class="fas fa-clock mr-3"></i> Informe Jornada
                </a>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6 nav-item">
                    <i class="fas fa-calendar-alt mr-3"></i> Vacaciones
                </a>
                <p class="pl-6 pt-4 pb-2 text-gray-400 text-xs uppercase">RRHH</p>
                <a href="#" class="flex items-center text-white opacity-75 hover:opacity-100 py-3 pl-6 nav-item">
                    <i class="fas fa-users mr-3"></i> Empleados
                </a>
            </nav>
        </div>

        <!-- Contenido Principal -->
        <div class="w-full">
            <!-- Barra Superior -->
            <header class="bg-white shadow p-4 flex justify-between items-center">
                <div class="text-xl font-semibold">Hola <?php echo $_SESSION['nombre']; ?>, ¡Bienvenido!</div>
                <div class="flex items-center">
                    <span class="mr-4 text-gray-600"><?php echo date('d/m/Y'); ?></span>
                    <a href="logout.php" class="text-red-500 font-bold">Salir</a>
                </div>
            </header>

            <main class="p-6">
                <!-- Fila de Tarjetas (Stats) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm uppercase">Trabajando</p>
                                <p class="text-3xl font-bold">9</p>
                            </div>
                            <i class="fas fa-sign-in-alt text-green-500 text-3xl"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm uppercase">No han fichado</p>
                                <p class="text-3xl font-bold">2</p>
                            </div>
                            <i class="fas fa-sign-out-alt text-red-500 text-3xl"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm uppercase">Total Empleados</p>
                                <p class="text-3xl font-bold">15</p>
                            </div>
                            <i class="fas fa-user-friends text-blue-500 text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Botón de Fichaje Dinámico -->
                <div class="bg-white rounded-lg shadow-sm p-8 text-center mb-8">
                    <h2 class="text-2xl font-bold mb-4">¿Qué quieres hacer hoy?</h2>
                    <div class="flex justify-center space-x-4">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-bold text-lg flex items-center shadow-lg transform transition active:scale-95">
                            <i class="fas fa-play mr-2"></i> ENTRAR
                        </button>
                        <button class="bg-amber-500 hover:bg-amber-600 text-white px-8 py-3 rounded-lg font-bold text-lg flex items-center shadow-lg transform transition active:scale-95">
                            <i class="fas fa-pause mr-2"></i> PAUSA
                        </button>
                        <button class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-bold text-lg flex items-center shadow-lg transform transition active:scale-95">
                            <i class="fas fa-stop mr-2"></i> SALIR
                        </button>
                    </div>
                </div>

                <!-- Sección Empleados (Grid de Fotos) -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-gray-700 font-bold mb-4 uppercase text-xs tracking-wider border-b pb-2">Empleados trabajando</h3>
                    <div class="flex flex-wrap gap-4">
                        <!-- Ejemplo de empleados -->
                        <div class="flex flex-col items-center">
                            <img src="https://i.pravatar.cc/150?u=1" class="w-12 h-12 rounded-full border-2 border-green-500 p-0.5">
                            <span class="text-xs mt-1">Ana P.</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <img src="https://i.pravatar.cc/150?u=2" class="w-12 h-12 rounded-full border-2 border-green-500 p-0.5">
                            <span class="text-xs mt-1">Juan R.</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <img src="https://i.pravatar.cc/150?u=3" class="w-12 h-12 rounded-full border-2 border-amber-500 p-0.5">
                            <span class="text-xs mt-1 text-amber-600 font-bold">Pausa</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

</body>
</html>
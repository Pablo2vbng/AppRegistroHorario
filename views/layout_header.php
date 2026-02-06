<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVTools HR Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        #mobile-drawer { transition: transform 0.3s ease-in-out; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen">

<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-50 shadow-xl">
    <div class="flex items-center space-x-3">
        <img src="assets/img/logoCvTools.jpg" alt="Logo" class="h-8 rounded">
        <span class="font-black text-xs tracking-tighter uppercase italic">CVTools</span>
    </div>
    <button onclick="document.getElementById('mobile-drawer').classList.remove('-translate-x-full')" class="text-2xl p-2 hover:text-blue-400 transition">
        <i class="fas fa-bars"></i>
    </button>
</div>

<div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-white z-[60] transform -translate-x-full shadow-2xl md:hidden">
    <div class="p-6 border-b border-slate-800 flex justify-between items-center">
        <img src="assets/img/logoCvTools.jpg" class="h-8 rounded">
        <button onclick="document.getElementById('mobile-drawer').classList.add('-translate-x-full')" class="text-2xl text-slate-400">&times;</button>
    </div>
    <nav class="p-4 space-y-2">
        <a href="index.php?p=dashboard" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-home mr-4 text-blue-400"></i> Escritorio</a>
        <a href="index.php?p=jornada" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-history mr-4 text-emerald-400"></i> Mi Jornada</a>
        <a href="index.php?p=solicitudes" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-calendar-plus mr-4 text-amber-400"></i> Vacaciones / Bajas</a>
        <?php if($_SESSION['rol'] == 'admin'): ?>
            <div class="pt-6 pb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest border-t border-slate-800 mt-4">RRHH</div>
            <a href="index.php?p=calendario_equipo" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-calendar-alt mr-4 text-rose-400"></i> Calendario Global</a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-file-invoice mr-4"></i> Informes Equipo</a>
            <a href="index.php?p=empleados" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-users-cog mr-4"></i> Plantilla</a>
        <?php endif; ?>
    </nav>
</div>

<div class="flex">
    <aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0">
        <div class="p-8 border-b border-slate-800 mb-4 text-center">
            <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-32 mx-auto rounded-xl shadow-lg border border-slate-700">
        </div>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto no-scrollbar">
            <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition group">
                <i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio
            </a>
            <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Personal</p>
            <a href="index.php?p=jornada" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-clock mr-3 text-emerald-500"></i> Mi Jornada</a>
            <a href="index.php?p=solicitudes" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-day mr-3 text-amber-500"></i> Vacaciones</a>

            <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6">Administración</p>
            <a href="index.php?p=calendario_equipo" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-rose-300">
                <i class="fas fa-calendar-alt mr-3"></i> Calendario Global
            </a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-amber-200">
                <i class="fas fa-file-contract mr-3"></i> Informes Equipo
            </a>
            <a href="index.php?p=gestion_ausencias" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-sky-300">
                <i class="fas fa-tasks mr-3"></i> Validar Solicitudes
            </a>
            <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
            <?php endif; ?>
        </nav>
        <div class="p-6 border-t border-slate-800">
            <a href="logout.php" class="flex items-center p-3 text-rose-500 font-black text-xs uppercase tracking-tighter">
                <i class="fas fa-sign-out-alt mr-3 text-lg"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col w-full min-w-0">
        <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b">
            <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest"><?php echo date('l, d F Y'); ?></div>
            <div class="font-bold text-slate-700"><?php echo $_SESSION['nombre']; ?></div>
        </header>
        <div class="p-4 md:p-10 w-full animate-in fade-in duration-500">
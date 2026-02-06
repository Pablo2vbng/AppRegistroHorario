<?php
// Contar solicitudes pendientes para la burbuja del menú
$stmtCount = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'");
$totalPendientes = $stmtCount->fetchColumn();
?>
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
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        #mobile-drawer { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen">

<!-- NAVEGACIÓN MÓVIL -->
<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-2">
        <img src="assets/img/logoCvTools.jpg" alt="Logo" class="h-8 rounded">
        <?php if($totalPendientes > 0): ?>
            <span class="bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black animate-bounce"><?php echo $totalPendientes; ?></span>
        <?php endif; ?>
    </div>
    <button onclick="document.getElementById('mobile-drawer').classList.toggle('-translate-x-full')" class="text-2xl p-2"><i class="fas fa-bars"></i></button>
</div>

<div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-white z-[60] transform -translate-x-full shadow-2xl md:hidden uppercase text-[10px] font-black">
    <nav class="p-6 space-y-4 tracking-widest">
        <a href="index.php?p=dashboard" class="block p-3">Escritorio</a>
        <a href="index.php?p=calendario_anual" class="block p-3">Calendario Anual</a>
    </nav>
</div>

<div class="flex">
    <!-- SIDEBAR PC -->
    <aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0">
        <div class="p-8 border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-full rounded-xl shadow-lg border border-slate-700">
        </div>
        <nav class="flex-1 px-4 space-y-1 pt-6">
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest">Panel</p>
            <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio</a>
            <a href="index.php?p=calendario_anual" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-alt mr-3 text-rose-400"></i> Calendario Anual</a>
            
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Personal</p>
            <a href="index.php?p=jornada" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-clock mr-3 text-emerald-500"></i> Mi Jornada</a>
            <a href="index.php?p=solicitudes" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-day mr-3 text-amber-500"></i> Vacaciones</a>

            <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 pt-6 border-t border-slate-800">Administración</p>
            <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-800 transition text-sky-300">
                <div class="flex items-center"><i class="fas fa-tasks mr-3"></i> Validar Solicitudes</div>
                <?php if($totalPendientes > 0): ?>
                    <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded-md font-black"><?php echo $totalPendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-amber-200"><i class="fas fa-file-contract mr-3"></i> Informes Equipo</a>
            <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
            <?php endif; ?>
        </nav>
        <div class="p-6 border-t border-slate-800 text-center">
            <a href="logout.php" class="text-rose-500 font-black text-[10px] uppercase">Cerrar Sesión</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col w-full min-w-0">
        <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b">
            <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest leading-none"><?php echo date('l, d F Y'); ?></div>
            <div class="font-bold text-slate-700"><?php echo $_SESSION['nombre']; ?></div>
        </header>
        <div class="p-4 md:p-10 w-full animate-in fade-in duration-500">
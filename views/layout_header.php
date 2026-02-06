<?php
$stmtCount = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'");
$totalPendientes = $stmtCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CVTools HR Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Pop-ups profesionales -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        #mobile-drawer { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen">

<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-[100] shadow-xl">
    <img src="assets/img/logoCvTools.jpg" alt="Logo" class="h-7 rounded">
    <button onclick="document.getElementById('mobile-drawer').classList.toggle('-translate-x-full')" class="text-2xl p-2"><i class="fas fa-bars"></i></button>
</div>

<div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-white z-[120] transform -translate-x-full shadow-2xl md:hidden flex flex-col">
    <nav class="flex-1 p-6 space-y-4 font-black text-[10px] uppercase tracking-widest">
        <a href="index.php?p=dashboard" class="block p-3">Escritorio</a>
        <?php if($_SESSION['rol'] != 'admin'): ?>
            <a href="index.php?p=jornada" class="block p-3">Mi Jornada</a>
            <a href="index.php?p=solicitudes" class="block p-3">Vacaciones</a>
        <?php else: ?>
            <a href="index.php?p=gestion_ausencias" class="block p-3 text-sky-400">Validar (<?php echo $totalPendientes; ?>)</a>
            <a href="index.php?p=empleados" class="block p-3">Plantilla</a>
        <?php endif; ?>
        <a href="logout.php" class="block p-3 text-rose-500">Salir</a>
    </nav>
</div>

<div class="flex">
    <!-- SIDEBAR PC -->
    <aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0">
        <div class="p-8 border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-full rounded-xl shadow-lg border border-slate-700">
        </div>
        <nav class="flex-1 px-4 space-y-1 pt-6 overflow-y-auto no-scrollbar">
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">General</p>
            <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio</a>
            <a href="index.php?p=calendario_anual" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-alt mr-3 text-rose-400"></i> Calendario Anual</a>
            
            <?php if($_SESSION['rol'] != 'admin'): ?>
                <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Mi Espacio</p>
                <a href="index.php?p=jornada" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-clock mr-3 text-emerald-500"></i> Mi Jornada</a>
                <a href="index.php?p=solicitudes" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-day mr-3 text-amber-500"></i> Mis Vacaciones</a>
            <?php endif; ?>

            <?php if($_SESSION['rol'] == 'admin'): ?>
                <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6">Gestión RRHH</p>
                <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-800 transition text-sky-300">
                    <div class="flex items-center"><i class="fas fa-tasks mr-3"></i> Validar Solicitudes</div>
                    <?php if($totalPendientes > 0): ?>
                        <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded font-black"><?php echo $totalPendientes; ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?p=informe_global" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-300"><i class="fas fa-file-export mr-3"></i> Informe Anual</a>
                <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
                <a href="index.php?p=gestion_festivos" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-glass-cheers mr-3"></i> Festivos</a>
            <?php endif; ?>
        </nav>
        <div class="p-6 border-t border-slate-800 text-center">
            <a href="logout.php" class="text-rose-500 font-black text-[10px] uppercase tracking-widest">Cerrar Sesión</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col w-full min-w-0">
        <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b">
            <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest"><?php echo date('l, d F Y'); ?></div>
            <div class="font-bold text-slate-700 uppercase text-xs"><?php echo $_SESSION['rol']; ?> • <?php echo $_SESSION['nombre']; ?></div>
        </header>
        <div class="p-4 md:p-10 w-full animate-in fade-in duration-500">
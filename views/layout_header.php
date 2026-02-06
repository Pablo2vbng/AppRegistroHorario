<?php
// Contar solicitudes pendientes para la burbuja
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
    <style>
        /* Optimizaciones para móvil */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        
        #mobile-drawer {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            overflow-y: auto;
        }
        
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen flex flex-col md:flex-row">

<!-- ==========================================
     MENÚ SUPERIOR MÓVIL (Sticky)
     ========================================== -->
<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-[100] shadow-2xl">
    <div class="flex items-center space-x-3">
        <img src="assets/img/logoCvTools.jpg" alt="Logo" class="h-7 rounded shadow-lg">
        <span class="font-black text-[10px] tracking-widest uppercase italic text-blue-400">CVTools</span>
    </div>
    <div class="flex items-center space-x-2">
        <?php if($_SESSION['rol'] == 'admin' && $totalPendientes > 0): ?>
            <span class="bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black animate-pulse">!</span>
        <?php endif; ?>
        <button onclick="toggleDrawer(true)" class="text-2xl p-2 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<!-- ==========================================
     DRAWER MÓVIL (MENÚ LATERAL MÓVIL)
     ========================================== -->
<div id="drawer-overlay" onclick="toggleDrawer(false)" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[110] hidden opacity-0 transition-opacity duration-300"></div>

<div id="mobile-drawer" class="fixed inset-y-0 right-0 w-[280px] bg-slate-900 text-slate-300 z-[120] transform translate-x-full shadow-[-20px_0_50px_-12px_rgba(0,0,0,0.5)] md:hidden flex flex-col">
    <!-- Header Drawer -->
    <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-950">
        <span class="font-black text-[10px] tracking-[0.3em] uppercase text-slate-500">Navegación</span>
        <button onclick="toggleDrawer(false)" class="text-2xl text-slate-400 hover:text-white">&times;</button>
    </div>

    <!-- Links Drawer (con scroll propio) -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto safe-area-bottom">
        <p class="px-4 py-2 text-[9px] font-black text-slate-600 uppercase tracking-widest">General</p>
        <a href="index.php?p=dashboard" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-desktop mr-4 text-blue-400"></i> Escritorio</a>
        <a href="index.php?p=calendario_anual" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-calendar-alt mr-4 text-rose-400"></i> Calendario Anual</a>
        
        <p class="px-4 py-2 text-[9px] font-black text-slate-600 uppercase tracking-widest mt-4">Personal</p>
        <a href="index.php?p=jornada" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-history mr-4 text-emerald-400"></i> Mi Jornada</a>
        <a href="index.php?p=solicitudes" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-umbrella-beach mr-4 text-amber-400"></i> Vacaciones</a>

        <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-2 text-[9px] font-black text-blue-500/50 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6">Administración Carmen</p>
            <a href="index.php?p=informe_global" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-file-export mr-4 text-emerald-300"></i> Informe Global</a>
            <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-4 hover:bg-slate-800 rounded-2xl transition">
                <div class="flex items-center"><i class="fas fa-tasks mr-4 text-sky-300"></i> Validar Solicitudes</div>
                <?php if($totalPendientes > 0): ?>
                    <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded font-black"><?php echo $totalPendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-file-contract mr-4 text-amber-200"></i> Informes Equipo</a>
            <a href="index.php?p=empleados" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-users-cog mr-4"></i> Plantilla</a>
            <a href="index.php?p=gestion_festivos" class="flex items-center p-4 hover:bg-slate-800 rounded-2xl transition"><i class="fas fa-glass-cheers mr-4"></i> Festivos</a>
        <?php endif; ?>

        <!-- BOTÓN SALIR MÓVIL -->
        <div class="mt-10 border-t border-slate-800 pt-6">
            <a href="logout.php" class="flex items-center p-4 text-rose-400 font-black uppercase text-xs tracking-widest bg-rose-500/5 rounded-2xl">
                <i class="fas fa-power-off mr-4"></i> Cerrar Sesión
            </a>
        </div>
    </nav>
</div>

<!-- ==========================================
     SIDEBAR PC (Visible solo >= 768px)
     ========================================== -->
<aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0">
    <div class="p-8 border-b border-slate-800">
        <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-full rounded-xl shadow-lg border border-slate-700">
    </div>
    <nav class="flex-1 px-4 space-y-1 pt-6 overflow-y-auto no-scrollbar">
        <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Mi Espacio</p>
        <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio</a>
        <a href="index.php?p=calendario_anual" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-alt mr-3 text-rose-400"></i> Calendario Anual</a>
        <a href="index.php?p=jornada" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-clock mr-3 text-emerald-500"></i> Mi Jornada</a>

        <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 pt-6 border-t border-slate-800">RRHH Carmen</p>
            <a href="index.php?p=informe_global" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-300 font-bold"><i class="fas fa-file-export mr-3"></i> Informe Anual Global</a>
            <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-800 transition text-sky-300">
                <div class="flex items-center"><i class="fas fa-tasks mr-3"></i> Validar Solicitudes</div>
                <?php if($totalPendientes > 0): ?>
                    <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded-md font-black"><?php echo $totalPendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
        <?php endif; ?>
    </nav>
    <div class="p-6 border-t border-slate-800 text-center">
        <a href="logout.php" class="text-rose-500 font-black text-[10px] uppercase hover:text-rose-400 transition tracking-widest">Cerrar Sesión</a>
    </div>
</aside>

<!-- CONTENIDO PRINCIPAL -->
<div class="flex-1 flex flex-col w-full min-w-0 overflow-x-hidden">
    <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b border-slate-200">
        <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest leading-none"><?php echo date('l, d F Y'); ?></div>
        <div class="font-bold text-slate-700 uppercase text-xs"><?php echo $_SESSION['rol']; ?> • <?php echo $_SESSION['nombre']; ?></div>
    </header>
    
    <div class="p-4 md:p-10 w-full">

<script>
function toggleDrawer(show) {
    const drawer = document.getElementById('mobile-drawer');
    const overlay = document.getElementById('drawer-overlay');
    if (show) {
        drawer.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.add('opacity-100'), 10);
    } else {
        drawer.classList.add('translate-x-full');
        overlay.classList.remove('opacity-100');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }
}
</script>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        #mobile-drawer { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        /* Fix para que el menú móvil no se corte */
        .drawer-content { height: 100%; overflow-y: auto; padding-bottom: 100px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900 min-h-screen flex flex-col md:flex-row">

<!-- HEADER MÓVIL (SIEMPRE VISIBLE EN SMARTPHONE) -->
<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-[100] shadow-xl no-print">
    <img src="assets/img/logoCvTools.jpg" class="h-7 rounded">
    <button onclick="document.getElementById('mobile-drawer').classList.toggle('translate-x-full')" class="text-2xl p-2 focus:outline-none">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- DRAWER MÓVIL (CORREGIDO CON SCROLL Y TODAS LAS OPCIONES) -->
<div id="mobile-drawer" class="fixed inset-y-0 right-0 w-72 bg-slate-900 text-white z-[120] transform translate-x-full shadow-2xl md:hidden flex flex-col no-print">
    <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-950">
        <span class="font-black text-[10px] tracking-widest uppercase text-slate-500 italic">Menú Principal</span>
        <button onclick="document.getElementById('mobile-drawer').classList.add('translate-x-full')" class="text-2xl text-slate-400">&times;</button>
    </div>
    
    <nav class="drawer-content p-6 space-y-4 font-black text-[10px] uppercase tracking-widest">
        <a href="index.php?p=dashboard" class="flex items-center p-3 hover:bg-slate-800 rounded-xl"><i class="fas fa-desktop mr-3 text-blue-400"></i> Escritorio</a>
        <a href="index.php?p=calendario_anual" class="flex items-center p-3 hover:bg-slate-800 rounded-xl"><i class="fas fa-calendar-alt mr-3 text-rose-400"></i> Calendario Anual</a>
        
        <!-- SECCIÓN PERSONAL (Solo empleados) -->
        <p class="text-slate-600 mt-6 border-t border-slate-800 pt-4 mb-2">Mi Espacio</p>
        <a href="index.php?p=perfil" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-purple-400"><i class="fas fa-user-circle mr-3"></i> Mi Perfil</a>
        <a href="index.php?p=solicitudes" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-amber-500"><i class="fas fa-paper-plane mr-3"></i> Solicitudes</a>
        <?php if($_SESSION['rol'] != 'admin'): ?>
            <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6 italic">Mis Informes</p>
            <a href="index.php?p=informe_legal&user_id=<?php echo $_SESSION['usuario_id']; ?>" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-400"><i class="fas fa-file-invoice mr-3"></i> Mensual</a>    
        <?php endif; ?>

        <!-- SECCIÓN ADMINISTRACIÓN (Visible para Admin en móvil) -->
        <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="text-slate-600 mt-6 border-t border-slate-800 pt-4 mb-2 italic text-sky-500">Administración</p>
            <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-3 hover:bg-slate-800 rounded-xl text-sky-300">
                <span><i class="fas fa-tasks mr-3"></i> Validar Solicitudes</span>
                <?php if($totalPendientes > 0): ?><span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded font-black"><?php echo $totalPendientes; ?></span><?php endif; ?>
            </a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-amber-200"><i class="fas fa-user-clock mr-3"></i> Control Horario</a>
            <a href="index.php?p=empleados" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-slate-300"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
            <a href="index.php?p=gestion_festivos" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-slate-300"><i class="fas fa-glass-cheers mr-3"></i> Festivos</a>
            <a href="index.php?p=informe_legal&user_id=<?php echo $_SESSION['usuario_id']; ?>" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-emerald-400"><i class="fas fa-file-invoice mr-3"></i> Listado Mensual</a>
            <a href="index.php?p=informe_global" class="flex items-center p-3 hover:bg-slate-800 rounded-xl text-blue-400"><i class="fas fa-file-export mr-3"></i> Resumen Anual</a>
        <?php endif; ?>

        <div class="pt-6">
            <a href="javascript:void(0)" onclick="confirmarSalida()" class="flex items-center p-4 text-rose-500 border-t border-slate-800 bg-rose-500/5 rounded-2xl">
                <i class="fas fa-power-off mr-3"></i> Cerrar Sesión
            </a>
        </div>
    </nav>
</div>

<!-- SIDEBAR PC -->
<aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0 no-print">
    <div class="p-8 border-b border-slate-800 text-center">
        <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-full rounded-xl shadow-lg border border-slate-700">
    </div>
    <nav class="flex-1 px-4 space-y-1 pt-6 overflow-y-auto no-scrollbar">
        <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">Mi Espacio</p>
        <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio</a>
        <a href="index.php?p=calendario_anual" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-calendar-alt mr-3 text-rose-400"></i> Calendario</a>
        <a href="index.php?p=perfil" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-purple-400"><i class="fas fa-user-circle mr-3"></i> Mi Perfil</a>
        
        <?php if($_SESSION['rol'] != 'admin'): ?>
            <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6 italic">Mis Informes</p>
            <a href="index.php?p=informe_legal&user_id=<?php echo $_SESSION['usuario_id']; ?>" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-400"><i class="fas fa-file-invoice mr-3"></i> Mensual</a>
            <a href="index.php?p=informe_global" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-blue-400"><i class="fas fa-file-export mr-3"></i> Anual</a>
        <?php endif; ?>

        <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 border-t border-slate-800 pt-6 italic text-sky-500">Administración</p>
            <a href="index.php?p=gestion_ausencias" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-800 transition text-sky-300">
                <div class="flex items-center"><i class="fas fa-tasks mr-3"></i> Validar Solicitudes</div>
                <?php if($totalPendientes > 0): ?><span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded font-black"><?php echo $totalPendientes; ?></span><?php endif; ?>
            </a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-amber-200"><i class="fas fa-user-clock mr-3"></i> Control Horario</a>
            <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
            <a href="index.php?p=gestion_festivos" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-glass-cheers mr-3"></i> Festivos</a>
            <a href="index.php?p=informe_legal&user_id=<?php echo $_SESSION['usuario_id']; ?>" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-400"><i class="fas fa-file-invoice mr-3"></i> Listado Mensual</a>
            <a href="index.php?p=informe_global" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-blue-400"><i class="fas fa-file-export mr-3"></i> Resumen Anual</a>
        <?php endif; ?>
    </nav>
    <div class="p-6 border-t border-slate-800 text-center">
        <a href="javascript:void(0)" onclick="confirmarSalida()" class="text-rose-500 font-black text-[10px] uppercase tracking-widest hover:text-rose-400 transition">Cerrar Sesión</a>
    </div>
</aside>

<div class="flex-1 flex flex-col w-full min-w-0 overflow-x-hidden">
    <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b border-slate-200 no-print">
        <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest"><?php echo date('l, d F Y'); ?></div>
        <div class="font-bold text-slate-700 uppercase text-xs"><?php echo $_SESSION['rol']; ?> • <?php echo $_SESSION['nombre']; ?></div>
    </header>
    <div class="p-4 md:p-10 w-full animate-in fade-in duration-500">

<script>
function confirmarSalida() {
    Swal.fire({
        title: '¿Cerrar sesión?', text: "Deberás volver a identificarte para acceder.", icon: 'question',
        showCancelButton: true, confirmButtonColor: '#0f172a', cancelButtonColor: '#f1f5f9',
        confirmButtonText: 'SÍ, SALIR', cancelButtonText: '<span style="color: #64748b">CANCELAR</span>', reverseButtons: true,
        customClass: { popup: 'rounded-[30px]', confirmButton: 'rounded-xl font-black text-[10px] uppercase px-6 py-3', cancelButton: 'rounded-xl font-black text-[10px] uppercase px-6 py-3' }
    }).then((result) => { if (result.isConfirmed) window.location.href = 'logout.php'; });
}
</script>
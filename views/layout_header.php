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
<div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center sticky top-0 z-50 shadow-xl no-print">
    <img src="assets/img/logoCvTools.jpg" alt="Logo" class="h-8 rounded">
    <button onclick="document.getElementById('mobile-drawer').classList.toggle('-translate-x-full')" class="text-2xl p-2"><i class="fas fa-bars"></i></button>
</div>

<!-- DRAWER MÓVIL -->
<div id="mobile-drawer" class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-white z-[60] transform -translate-x-full shadow-2xl md:hidden">
    <div class="p-6 border-b border-slate-800 flex justify-between items-center text-[10px] font-black uppercase">
        <span>Menú Principal</span>
        <button onclick="document.getElementById('mobile-drawer').classList.add('-translate-x-full')" class="text-2xl">&times;</button>
    </div>
    <nav class="p-4 space-y-4 uppercase text-xs font-bold tracking-widest">
        <a href="index.php?p=dashboard" class="block p-3 hover:bg-slate-800 rounded-xl"><i class="fas fa-home mr-3 text-blue-400"></i> Escritorio</a>
        <a href="index.php?p=jornada" class="block p-3 hover:bg-slate-800 rounded-xl"><i class="fas fa-history mr-3 text-emerald-400"></i> Mi Jornada</a>
        <a href="index.php?p=perfil" class="block p-3 hover:bg-slate-800 rounded-xl"><i class="fas fa-user mr-3 text-purple-400"></i> Mi Perfil</a>
    </nav>
</div>

<div class="flex">
    <!-- SIDEBAR PC -->
    <aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-300 min-h-screen sticky top-0 shadow-2xl flex-shrink-0 no-print">
        <div class="p-8 border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="Logo" class="w-full rounded-xl shadow-lg border border-slate-700">
        </div>
        <nav class="flex-1 px-4 space-y-1 pt-4 overflow-y-auto">
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest">Mi Espacio</p>
            <a href="index.php?p=dashboard" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-desktop mr-3 text-blue-500"></i> Escritorio</a>
            <a href="index.php?p=jornada" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-clock mr-3 text-emerald-500"></i> Mi Jornada</a>
            <a href="index.php?p=perfil" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-user-circle mr-3 text-purple-400"></i> Mi Perfil</a>
            <a href="index.php?p=documentos" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-folder mr-3 text-rose-400"></i> Documentos</a>

            <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6 pt-6 border-t border-slate-800">Administración</p>
            <a href="index.php?p=informe_legal" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition text-emerald-300 font-bold"><i class="fas fa-file-signature mr-3"></i> Registro Legal</a>
            <a href="index.php?p=informes_equipo" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-file-invoice mr-3 text-amber-200"></i> Informes Equipo</a>
            <a href="index.php?p=empleados" class="flex items-center p-3 rounded-xl hover:bg-slate-800 transition"><i class="fas fa-users-cog mr-3"></i> Plantilla</a>
            <?php endif; ?>

            <div class="mt-8 px-2">
                <a href="https://wa.me/<?php echo GESTORA_WHATSAPP; ?>?text=Hola%20Carmen,%20tengo%20un%20problema" target="_blank" 
                   class="flex items-center justify-center bg-emerald-600/10 hover:bg-emerald-600 border border-emerald-600/30 text-emerald-500 hover:text-white p-3 rounded-xl transition text-[10px] font-black uppercase">
                    <i class="fab fa-whatsapp mr-2 text-base"></i> Avisar a Carmen
                </a>
            </div>
        </nav>
        <div class="p-6 border-t border-slate-800 text-center">
            <a href="logout.php" class="text-rose-500 font-black text-[10px] uppercase hover:text-rose-400 transition tracking-widest">Cerrar Sesión</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col w-full min-w-0">
        <header class="hidden md:flex bg-white shadow-sm px-10 py-5 justify-between items-center border-b border-slate-200 no-print">
            <div class="font-black text-slate-800 text-sm uppercase italic tracking-widest"><?php echo date('l, d F Y'); ?></div>
            <div class="flex items-center space-x-4 font-bold text-slate-600">
                <span class="text-xs font-black text-slate-400 bg-slate-100 px-3 py-1 rounded-lg uppercase"><?php echo $_SESSION['rol']; ?></span>
                <span><?php echo $_SESSION['nombre']; ?></span>
            </div>
        </header>
        <div class="p-4 md:p-10 w-full">
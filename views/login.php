<?php
require_once 'config/config.php';

// Si ya está logueado, lo mandamos al dashboard directamente
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Capturamos posibles errores de login enviados desde index.php
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CVTools HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Animación suave para el mensaje de error */
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 md:p-12 rounded-[40px] shadow-2xl w-full max-w-md border border-slate-200">
        <div class="text-center mb-10">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools Logo" class="mx-auto mb-6 w-48 rounded-xl shadow-sm">
            <p class="text-slate-400 text-xs font-black uppercase tracking-[0.3em]">Gestión Horaria Benigànim</p>
        </div>
        
        <?php if ($error): ?>
            <div class="fade-in mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-2xl text-red-700 text-xs font-bold uppercase tracking-wider">
                <?php 
                    if ($error == 'auth') echo "Email o contraseña incorrectos";
                    else if ($error == 'access') echo "Debes iniciar sesión primero";
                    else echo "Error en el sistema, intenta de nuevo";
                ?>
            </div>
        <?php endif; ?>
        
        <form action="index.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2 tracking-widest pl-2">Email Corporativo</label>
                <input type="email" name="email" autocomplete="email" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-3xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none font-bold transition-all" placeholder="ejemplo@cvtools.es" required>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2 tracking-widest pl-2">Contraseña</label>
                <input type="password" name="password" autocomplete="current-password" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-3xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none font-bold transition-all" placeholder="••••••••" required>
            </div>
            <button type="submit" class="w-full bg-slate-900 text-white font-black py-5 rounded-3xl hover:bg-blue-600 transition-all shadow-xl shadow-blue-900/10 active:scale-95 uppercase text-xs tracking-widest">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-10 text-center">
            <p class="text-[9px] font-bold text-slate-300 uppercase tracking-tighter">© <?php echo date('Y'); ?> CV TOOLS S.L. • Innovai.es</p>
        </div>
    </div>
</body>
</html>
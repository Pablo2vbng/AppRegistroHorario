<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CVTools HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 md:p-12 rounded-[40px] shadow-2xl w-full max-w-md border border-slate-200">
        <div class="text-center mb-10">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools Logo" class="mx-auto mb-6 w-48 rounded-xl shadow-sm">
            <p class="text-slate-400 text-xs font-black uppercase tracking-[0.3em]">Gestión Horaria Benigànim</p>
        </div>
        
        <form action="index.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2 tracking-widest pl-2">Email Corporativo</label>
                <input type="email" name="email" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-3xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none font-bold transition-all" placeholder="ejemplo@cvtools.es" required>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase mb-2 tracking-widest pl-2">Contraseña</label>
                <input type="password" name="password" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-3xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none font-bold transition-all" placeholder="••••••••" required>
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
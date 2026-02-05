<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - CVTools HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <div class="text-center mb-8">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools Logo" class="mx-auto mb-4 w-48">
            <p class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Gestión Horaria</p>
        </div>
        <form action="index.php" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email Corporativo</label>
                <input type="email" name="email" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
                <input type="password" name="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-slate-800 text-white font-bold py-2 rounded hover:bg-slate-900 transition shadow-lg">ENTRAR</button>
        </form>
    </div>
</body>
</html>
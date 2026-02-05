<?php
// Seguridad: Solo admin o rrhh pueden ver esto
if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 'empleado') {
    header("Location: index.php");
    exit();
}

// Obtener lista de empleados
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC");
$empleados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantilla - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen font-sans">

    <!-- Sidebar (Mantenemos la estética) -->
    <div class="bg-slate-900 w-64 hidden md:block flex-shrink-0 text-slate-300">
        <div class="p-6 text-center border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools" class="w-32 mx-auto rounded shadow-lg">
        </div>
        <nav class="text-sm font-semibold pt-4">
            <a href="index.php?p=dashboard" class="flex items-center hover:text-white py-4 pl-6 transition">
                <i class="fas fa-desktop mr-3"></i> Escritorio
            </a>
            <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest border-t border-slate-800">Administración</p>
            <a href="index.php?p=empleados" class="flex items-center text-white py-4 pl-6 bg-slate-800 border-l-4 border-blue-500">
                <i class="fas fa-users-cog mr-3"></i> Plantilla
            </a>
            <a href="logout.php" class="flex items-center text-rose-400 hover:text-rose-300 py-4 pl-6 transition mt-10">
                <i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
            </a>
        </nav>
    </div>

    <!-- Contenido Principal -->
    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm p-6 flex justify-between items-center border-b">
            <div>
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Gestión de Plantilla</h1>
                <p class="text-slate-400 text-sm">Administra los accesos y vacaciones de tu equipo</p>
            </div>
            <button onclick="toggleModal(true)" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition transform active:scale-95 flex items-center">
                <i class="fas fa-user-plus mr-2"></i> Añadir Empleado
            </button>
        </header>

        <main class="p-8 max-w-7xl mx-auto w-full">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Empleado</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Email Corporativo</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Permisos</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider text-center">Vacaciones</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($empleados as $emp): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-5 flex items-center">
                                <img src="https://ui-avatars.com/api/?background=random&name=<?php echo urlencode($emp['nombre']); ?>" class="w-10 h-10 rounded-xl mr-4 shadow-sm">
                                <div>
                                    <p class="font-bold text-slate-700"><?php echo $emp['nombre']; ?></p>
                                    <p class="text-xs text-slate-400 italic">Alta: <?php echo $emp['fecha_alta']; ?></p>
                                </div>
                            </td>
                            <td class="p-5 text-slate-600 font-medium"><?php echo $emp['email']; ?></td>
                            <td class="p-5">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase <?php echo $emp['rol'] == 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo $emp['rol']; ?>
                                </span>
                            </td>
                            <td class="p-5 text-center">
                                <span class="text-slate-800 font-black"><?php echo $emp['dias_vacaciones_disponibles']; ?></span>
                                <span class="text-slate-400 text-xs">/ 22</span>
                            </td>
                            <td class="p-5 text-right space-x-2">
                                <button class="text-slate-300 hover:text-blue-600 transition"><i class="fas fa-edit"></i></button>
                                <button class="text-slate-300 hover:text-rose-600 transition"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL PARA NUEVO EMPLEADO -->
    <div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="bg-slate-800 p-6 text-white flex justify-between items-center">
                <h3 class="font-black uppercase tracking-widest text-sm">Nuevo Miembro de Equipo</h3>
                <button onclick="toggleModal(false)" class="text-slate-400 hover:text-white text-xl">&times;</button>
            </div>
            
            <form id="formNuevoEmpleado" class="p-8 space-y-5">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Nombre Completo</label>
                    <input type="text" name="nombre" required class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Email Corporativo</label>
                    <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Contraseña Inicial</label>
                    <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Rol en la empresa</label>
                    <select name="rol" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                        <option value="empleado">Empleado</option>
                        <option value="admin">Administrador / RRHH</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-black py-4 rounded-2xl shadow-lg transition active:scale-95 uppercase tracking-widest">
                        Crear Cuenta
                    </button>
                </div>
                <p id="modal-error" class="text-center text-rose-500 text-xs font-bold"></p>
            </form>
        </div>
    </div>

    <script>
    function toggleModal(show) {
        const modal = document.getElementById('modalNuevo');
        modal.classList.toggle('hidden', !show);
    }

    document.getElementById('formNuevoEmpleado').onsubmit = function(e) {
        e.preventDefault();
        const errorDiv = document.getElementById('modal-error');
        const formData = new FormData(this);

        fetch('api/empleados_crear.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                errorDiv.innerText = "Error: " + data.message;
            }
        });
    }
    </script>
</body>
</html>
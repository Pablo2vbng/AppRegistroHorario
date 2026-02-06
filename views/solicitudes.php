<?php
require_once 'config/config.php';
$usuario_id = $_SESSION['usuario_id'];

// Obtener mis solicitudes previas
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE usuario_id = ? ORDER BY fecha_inicio DESC");
$stmt->execute([$usuario_id]);
$mis_solicitudes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen font-sans">

    <!-- Sidebar (Reutilizado) -->
    <div class="bg-slate-900 w-64 hidden md:block flex-shrink-0 text-slate-300">
        <div class="p-6 text-center border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools" class="w-32 mx-auto rounded shadow-lg">
        </div>
        <nav class="text-sm font-semibold pt-4">
            <a href="index.php?p=dashboard" class="flex items-center hover:text-white py-4 pl-6 transition"><i class="fas fa-desktop mr-3"></i> Escritorio</a>
            <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest border-t border-slate-800">Mi Espacio</p>
            <a href="index.php?p=jornada" class="flex items-center hover:text-white py-4 pl-6 transition"><i class="fas fa-history mr-3"></i> Mi Jornada</a>
            <a href="index.php?p=solicitudes" class="flex items-center text-white py-4 pl-6 bg-slate-800 border-l-4 border-blue-500"><i class="fas fa-calendar-plus mr-3"></i> Vacaciones/Ausencias</a>
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm p-6 flex justify-between items-center border-b">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Solicitudes de Ausencia</h1>
        </header>

        <main class="p-8 max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- FORMULARIO DE SOLICITUD -->
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold text-slate-800 mb-6 uppercase tracking-wider text-sm">Nueva Solicitud</h2>
                    <form id="formSolicitud" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase mb-2">Tipo de Ausencia</label>
                            <select name="tipo" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                                <option value="vacaciones">Vacaciones</option>
                                <option value="medico">Asistencia Médica</option>
                                <option value="personal">Asuntos Propios</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase mb-2">Desde</label>
                                <input type="date" name="fecha_inicio" required class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase mb-2">Hasta</label>
                                <input type="date" name="fecha_fin" required class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase mb-2">Motivo / Comentario</label>
                            <textarea name="motivo" rows="3" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl outline-none" placeholder="Opcional..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition active:scale-95 uppercase text-xs tracking-widest">
                            Enviar Solicitud
                        </button>
                    </form>
                    <div id="status-msg" class="mt-4 text-center text-sm font-bold"></div>
                </div>
            </div>

            <!-- LISTADO DE MIS SOLICITUDES -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b">
                            <tr>
                                <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-widest">Tipo</th>
                                <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-widest">Periodo</th>
                                <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-widest text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($mis_solicitudes as $sol): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-5">
                                    <span class="font-bold text-slate-700 uppercase text-xs"><?php echo $sol['tipo']; ?></span>
                                </td>
                                <td class="p-5 text-slate-600 text-sm">
                                    <i class="far fa-calendar-alt mr-2 text-slate-300"></i>
                                    <?php echo date('d M', strtotime($sol['fecha_inicio'])); ?> - <?php echo date('d M', strtotime($sol['fecha_fin'])); ?>
                                </td>
                                <td class="p-5 text-center">
                                    <?php 
                                    $colores = ['pendiente' => 'bg-amber-100 text-amber-600', 'aprobado' => 'bg-emerald-100 text-emerald-600', 'rechazado' => 'bg-rose-100 text-rose-600'];
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo $colores[$sol['estado']]; ?>">
                                        <?php echo $sol['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('formSolicitud').onsubmit = function(e) {
        e.preventDefault();
        const msg = document.getElementById('status-msg');
        msg.innerText = "Enviando...";
        
        fetch('api/solicitudes_crear.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                msg.className = "mt-4 text-center text-rose-500 text-sm font-bold";
                msg.innerText = "Error: " + data.message;
            }
        });
    }
    </script>
</body>
</html>
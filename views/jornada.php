<?php
require_once 'config/config.php';

$usuario_id = $_SESSION['usuario_id'];
$mes_actual = date('m');
$anio_actual = date('Y');

// Consultamos todos los fichajes del mes para el usuario
$stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
$stmt->execute([$usuario_id, $mes_actual, $anio_actual]);
$fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupamos fichajes por día para calcular horas
$jornadas = [];
foreach ($fichajes as $f) {
    $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
    $jornadas[$fecha][] = $f;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Jornada - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen font-sans">

    <!-- Sidebar -->
    <div class="bg-slate-900 w-64 hidden md:block flex-shrink-0 text-slate-300">
        <div class="p-6 text-center border-b border-slate-800">
            <img src="assets/img/logoCvTools.jpg" alt="CVTools" class="w-32 mx-auto rounded shadow-lg">
        </div>
        <nav class="text-sm font-semibold pt-4">
            <a href="index.php?p=dashboard" class="flex items-center hover:text-white py-4 pl-6 transition">
                <i class="fas fa-desktop mr-3"></i> Escritorio
            </a>
            <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest border-t border-slate-800">Informes</p>
            <a href="index.php?p=jornada" class="flex items-center text-white py-4 pl-6 bg-slate-800 border-l-4 border-blue-500">
                <i class="fas fa-history mr-3"></i> Mi Jornada
            </a>
            <?php if($_SESSION['rol'] == 'admin'): ?>
            <p class="pl-6 pt-6 pb-2 text-slate-500 text-xs uppercase tracking-widest">Administración</p>
            <a href="index.php?p=empleados" class="flex items-center hover:text-white py-4 pl-6 transition">
                <i class="fas fa-users-cog mr-3"></i> Plantilla
            </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm p-6 flex justify-between items-center border-b">
            <div>
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Registro de Jornada</h1>
                <p class="text-slate-400 text-sm">Resumen de actividad: <?php echo date('F Y'); ?></p>
            </div>
            <button onclick="window.print()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-bold hover:bg-slate-200 transition flex items-center">
                <i class="fas fa-print mr-2"></i> Exportar PDF
            </button>
        </header>

        <main class="p-8 max-w-7xl mx-auto w-full">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Fecha</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Primera Entrada</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Última Salida</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider">Estado</th>
                            <th class="p-5 text-slate-500 font-black text-xs uppercase tracking-wider text-right">Total Horas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        foreach (array_reverse($jornadas) as $fecha => $eventos): 
                            $entrada = $eventos[0]['fecha_hora'];
                            $salida = end($eventos);
                            $hora_entrada = date('H:i:s', strtotime($entrada));
                            $hora_salida = ($salida['tipo'] == 'salida') ? date('H:i:s', strtotime($salida['fecha_hora'])) : '---';
                            
                            // Cálculo básico de horas (Diferencia entre primera entrada y última salida hoy)
                            $total_segundos = 0;
                            if ($salida['tipo'] == 'salida') {
                                $total_segundos = strtotime($salida['fecha_hora']) - strtotime($entrada);
                            }
                            $horas = floor($total_segundos / 3600);
                            $minutos = floor(($total_segundos / 60) % 60);
                        ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-5 font-bold text-slate-700">
                                <?php echo date('d/m/Y', strtotime($fecha)); ?>
                            </td>
                            <td class="p-5 text-emerald-600 font-mono font-bold">
                                <?php echo $hora_entrada; ?>
                            </td>
                            <td class="p-5 text-rose-600 font-mono font-bold">
                                <?php echo $hora_salida; ?>
                            </td>
                            <td class="p-5">
                                <?php if($salida['tipo'] != 'salida'): ?>
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-[10px] font-black uppercase tracking-widest animate-pulse">En curso</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded text-[10px] font-black uppercase tracking-widest">Completado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5 text-right font-black text-slate-800 text-lg">
                                <?php echo ($total_segundos > 0) ? "{$horas}h {$minutos}m" : "---"; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($jornadas)): ?>
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-400 italic">No hay registros este mes.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
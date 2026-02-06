<?php
// 1. Horas totales por día (Últimos 7 días)
$sqlGrafico = "
    SELECT DATE(fecha_hora) as dia, 
    SUM(TIMESTAMPDIFF(SECOND, 
        (SELECT MIN(f2.fecha_hora) FROM fichajes f2 WHERE DATE(f2.fecha_hora) = DATE(f1.fecha_hora) AND f2.usuario_id = f1.usuario_id AND f2.tipo = 'entrada'),
        (SELECT MAX(f3.fecha_hora) FROM fichajes f3 WHERE DATE(f3.fecha_hora) = DATE(f1.fecha_hora) AND f3.usuario_id = f1.usuario_id AND f3.tipo = 'salida')
    )) / 3600 as horas_totales
    FROM fichajes f1
    WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_hora)
    ORDER BY dia ASC";
$stmtGrafico = $pdo->query($sqlGrafico);
$datosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

// 2. Ranking de horas por empleado (Mes actual)
$sqlRanking = "
    SELECT u.nombre, SUM(TIMESTAMPDIFF(HOUR, f_in.fecha_hora, f_out.fecha_hora)) as total_horas
    FROM usuarios u
    JOIN fichajes f_in ON u.id = f_in.usuario_id AND f_in.tipo = 'entrada'
    JOIN fichajes f_out ON u.id = f_out.usuario_id AND f_out.tipo = 'salida' 
         AND DATE(f_in.fecha_hora) = DATE(f_out.fecha_hora)
    WHERE MONTH(f_in.fecha_hora) = MONTH(CURDATE())
    GROUP BY u.id
    ORDER BY total_horas DESC";
$ranking = $pdo->query($sqlRanking)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas - CVTools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Librería de Gráficos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">

    <!-- Reutiliza el Sidebar que ya tenemos -->

    <div class="flex-1 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tighter italic">Analítica de Empresa</h1>
            <p class="text-slate-500 font-medium">Rendimiento y actividad del equipo en el mes de <?php echo date('F'); ?></p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- GRÁFICO DE ACTIVIDAD -->
            <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                <h3 class="text-slate-400 text-xs font-black uppercase mb-6 tracking-widest">Horas Totales (Últimos 7 días)</h3>
                <canvas id="graficoActividad" height="150"></canvas>
            </div>

            <!-- RANKING DE EMPLEADOS -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                <h3 class="text-slate-400 text-xs font-black uppercase mb-6 tracking-widest">Ranking de Esfuerzo (Mensual)</h3>
                <div class="space-y-6">
                    <?php foreach($ranking as $r): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['nombre']); ?>&background=random" class="w-8 h-8 rounded-lg mr-3">
                            <span class="text-slate-700 font-bold text-sm"><?php echo $r['nombre']; ?></span>
                        </div>
                        <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full font-black text-xs">
                            <?php echo round($r['total_horas'], 1); ?> h
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($ranking)) echo '<p class="text-slate-400 text-sm italic">Sin datos este mes.</p>'; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
    const ctx = document.getElementById('graficoActividad').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php foreach($datosGrafico as $d) echo "'" . date('d M', strtotime($d['dia'])) . "',"; ?>],
            datasets: [{
                label: 'Horas Totales del Equipo',
                data: [<?php foreach($datosGrafico as $d) echo $d['horas_totales'] . ","; ?>],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
    </script>
</body>
</html>
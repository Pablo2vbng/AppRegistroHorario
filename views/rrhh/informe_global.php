<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Filtro de usuarios: SIEMPRE excluimos a los admins (Carmen, Jaime, Remi)
$sqlUsers = "SELECT * FROM usuarios WHERE rol = 'employee' OR rol = 'empleado'";
if ($_SESSION['rol'] == 'empleado') {
    $sqlUsers .= " AND id = " . intval($_SESSION['usuario_id']);
}
$usuarios = $pdo->query($sqlUsers . " ORDER BY nombre ASC")->fetchAll();

$datosPlantilla = [];
foreach($usuarios as $u) {
    // Cálculo de horas anuales efectivas
    $stmtH = $pdo->prepare("SELECT fecha_hora, tipo FROM fichajes WHERE usuario_id = ? AND YEAR(fecha_hora) = ?");
    $stmtH->execute([$u['id'], $anio]);
    $fichajes = $stmtH->fetchAll();
    
    $segTotales = 0; $inicio = null;
    foreach($fichajes as $f) {
        if($f['tipo'] == 'entrada' || $f['tipo'] == 'reanudar') $inicio = strtotime($f['fecha_hora']);
        if(($f['tipo'] == 'pausa' || $f['tipo'] == 'salida') && $inicio) {
            $segTotales += (strtotime($f['fecha_hora']) - $inicio);
            $inicio = null;
        }
    }

    $stmtA = $pdo->prepare("SELECT tipo, COUNT(*) as total FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND YEAR(fecha_inicio) = ? GROUP BY tipo");
    $stmtA->execute([$u['id'], $anio]);
    $aus = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);

    $datosPlantilla[] = [
        'nombre' => $u['nombre'],
        'horas' => floor($segTotales/3600),
        'v_gastadas' => $u['dias_vacaciones_totales'] - $u['dias_vacaciones_disponibles'],
        'v_disp' => $u['dias_vacaciones_disponibles'],
        'medico' => $aus['medico'] ?? 0
    ];
}
?>

<div class="max-w-6xl mx-auto pb-20">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 no-print px-2">
        <div class="text-center md:text-left">
            <h1 class="text-2xl md:text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Resumen Global <?php echo $anio; ?></h1>
            <p class="text-slate-400 font-bold text-sm">Resumen de jornada anual consolidada</p>
        </div>
        <button onclick="window.print()" class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-3xl font-black text-[10px] uppercase tracking-widest shadow-2xl">
            <i class="fas fa-print mr-2"></i> Imprimir Informe
        </button>
    </div>

    <!-- TABLA (OCULTA DUEÑOS AUTOMÁTICAMENTE) -->
    <div class="bg-white p-8 md:p-12 rounded-[50px] shadow-sm border border-slate-200 print:border-none print:p-0">
        <div class="flex justify-between items-center mb-12 border-b pb-8">
            <img src="<?php echo EMPRESA_LOGO; ?>" class="h-10 rounded">
            <div class="text-right">
                <p class="font-black text-slate-800 uppercase italic tracking-tighter"><?php echo EMPRESA_NOMBRE; ?></p>
                <p class="text-[9px] font-bold text-slate-400 uppercase">Resumen Anual <?php echo $anio; ?></p>
            </div>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black uppercase text-slate-400 bg-slate-50 border-y">
                    <th class="p-6">Trabajador</th>
                    <th class="p-6 text-center">Horas Año</th>
                    <th class="p-6 text-center">Vac. Gastadas</th>
                    <th class="p-6 text-center">Bajas</th>
                    <th class="p-6 text-right">Vac. Libres</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($datosPlantilla as $d): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-black text-slate-700 uppercase text-xs italic"><?php echo $d['nombre']; ?></td>
                    <td class="p-6 text-center font-mono font-black text-emerald-600"><?php echo $d['horas']; ?> h</td>
                    <td class="p-6 text-center font-bold text-slate-400"><?php echo $d['v_gastadas']; ?> d</td>
                    <td class="p-6 text-center font-bold text-rose-400"><?php echo $d['medico']; ?></td>
                    <td class="p-6 text-right font-black text-blue-600 text-lg"><?php echo round($d['v_disp'], 1); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
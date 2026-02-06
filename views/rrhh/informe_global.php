<?php
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// 1. Obtener todos los trabajadores
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();

// 2. Preparar datos agregados para cada uno
$datosPlantilla = [];
foreach($usuarios as $u) {
    // Calcular Horas Totales Trabajadas en el año
    $sqlHoras = "SELECT fecha_hora, tipo FROM fichajes WHERE usuario_id = ? AND YEAR(fecha_hora) = ?";
    $stmtH = $pdo->prepare($sqlHoras);
    $stmtH->execute([$u['id'], $anio]);
    $fichajes = $stmtH->fetchAll();
    
    $segundosTotales = 0; $inicioTramo = null;
    foreach($fichajes as $f) {
        if($f['tipo'] == 'entrada' || $f['tipo'] == 'reanudar') $inicioTramo = strtotime($f['fecha_hora']);
        if(($f['tipo'] == 'pausa' || $f['tipo'] == 'salida') && $inicioTramo) {
            $segundosTotales += (strtotime($f['fecha_hora']) - $inicioTramo);
            $inicioTramo = null;
        }
    }

    // Contar ausencias aprobadas por tipo
    $stmtA = $pdo->prepare("SELECT tipo, COUNT(*) as total FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND YEAR(fecha_inicio) = ? GROUP BY tipo");
    $stmtA->execute([$u['id'], $anio]);
    $ausencias = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);

    $datosPlantilla[] = [
        'nombre' => $u['nombre'],
        'horas' => floor($segundosTotales/3600),
        'vacaciones' => $u['dias_vacaciones_totales'] - $u['dias_vacaciones_disponibles'],
        'vac_disp' => $u['dias_vacaciones_disponibles'],
        'medico' => $ausencias['medico'] ?? 0,
        'asuntos' => $ausencias['personal'] ?? 0
    ];
}
?>

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-10 no-print">
        <div>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Informe Anual de Plantilla <?php echo $anio; ?></h1>
            <p class="text-slate-400 font-bold text-sm">Resumen global de actividad en Benigànim</p>
        </div>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl">
            <i class="fas fa-print mr-2"></i> Imprimir Informe Global
        </button>
    </div>

    <div class="bg-white p-10 rounded-[50px] shadow-sm border border-slate-200 print:shadow-none print:border-none print:p-0">
        <div class="flex justify-between items-center mb-10 border-b pb-8">
            <img src="assets/img/logoCvTools.jpg" class="h-12 rounded shadow-sm">
            <div class="text-right">
                <p class="font-black text-slate-800 uppercase italic">CV TOOLS S.L.</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Resumen General Año <?php echo $anio; ?></p>
            </div>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-50">
                    <th class="p-6 rounded-l-2xl">Trabajador</th>
                    <th class="p-6 text-center">Horas Realizadas</th>
                    <th class="p-6 text-center">Vacaciones Gastadas</th>
                    <th class="p-6 text-center">Bajas Médicas</th>
                    <th class="p-6 text-right rounded-r-2xl">Vac. Disponibles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($datosPlantilla as $d): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-black text-slate-700"><?php echo $d['nombre']; ?></td>
                    <td class="p-6 text-center font-mono font-bold text-emerald-600"><?php echo $d['horas']; ?> h</td>
                    <td class="p-6 text-center font-bold text-slate-400"><?php echo $d['vacaciones']; ?> días</td>
                    <td class="p-6 text-center font-bold text-rose-400"><?php echo $d['medico']; ?> b.m.</td>
                    <td class="p-6 text-right font-black text-blue-600"><?php echo $d['vac_disp']; ?> días</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-20 flex justify-between border-t border-slate-100 pt-10 text-center opacity-0 print:opacity-100">
            <div class="w-64 border-t border-slate-900 pt-2 text-[8px] font-black uppercase text-slate-400">Responsable de RRHH</div>
            <div class="w-64 border-t border-slate-900 pt-2 text-[8px] font-black uppercase text-slate-400">Sello de Empresa</div>
        </div>
    </div>
</div>

<style>
@media print {
    aside, header, .no-print { display: none !important; }
    body { background: white !important; }
    .rounded-[50px] { border-radius: 0 !important; }
}
</style>
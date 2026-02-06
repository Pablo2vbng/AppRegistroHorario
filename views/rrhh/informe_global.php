<?php
if ($_SESSION['rol'] != 'admin') exit();
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();

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
            <p class="text-slate-400 font-bold text-sm">Estadísticas anuales de la plantilla</p>
        </div>
        <button onclick="window.print()" class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-3xl font-black text-[10px] uppercase tracking-widest shadow-2xl">
            <i class="fas fa-print mr-2"></i> Generar PDF para Carmen
        </button>
    </div>

    <!-- VISTA MÓVIL (FICHAS) -->
    <div class="grid grid-cols-1 gap-6 md:hidden px-2 no-print">
        <?php foreach($datosPlantilla as $d): ?>
            <div class="bg-white p-8 rounded-[45px] shadow-sm border border-slate-200">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center font-black italic">
                        <?php echo substr($d['nombre'], 0, 1); ?>
                    </div>
                    <h3 class="font-black text-slate-800 uppercase tracking-tighter"><?php echo $d['nombre']; ?></h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Horas Año</p>
                        <p class="text-xl font-black text-emerald-600"><?php echo $d['horas']; ?> h</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Vac. Libres</p>
                        <p class="text-xl font-black text-blue-600"><?php echo $d['v_disp']; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Vac. Gastadas</p>
                        <p class="text-xl font-black text-slate-400"><?php echo $d['v_gastadas']; ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Bajas Médicas</p>
                        <p class="text-xl font-black text-rose-400"><?php echo $d['medico']; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- VISTA DESKTOP (TABLA CLÁSICA) -->
    <div class="hidden md:block bg-white p-12 rounded-[50px] shadow-sm border border-slate-200 print:shadow-none print:border-none print:p-0">
        <div class="flex justify-between items-center mb-12 border-b pb-8">
            <img src="assets/img/logoCvTools.jpg" class="h-10 rounded">
            <div class="text-right">
                <p class="font-black text-slate-800 uppercase italic tracking-tighter">CV TOOLS S.L.</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">Informe Consolidado Plantilla <?php echo $anio; ?></p>
            </div>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black uppercase text-slate-400 bg-slate-50 border-y border-slate-100">
                    <th class="p-6">Trabajador</th>
                    <th class="p-6 text-center">Horas Anuales</th>
                    <th class="p-6 text-center">Vac. Gastadas</th>
                    <th class="p-6 text-center">Bajas</th>
                    <th class="p-6 text-right">Disponibles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($datosPlantilla as $d): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-black text-slate-700 uppercase text-xs"><?php echo $d['nombre']; ?></td>
                    <td class="p-6 text-center font-mono font-black text-emerald-600"><?php echo $d['horas']; ?> h</td>
                    <td class="p-6 text-center font-bold text-slate-400"><?php echo $d['v_gastadas']; ?></td>
                    <td class="p-6 text-center font-bold text-rose-400"><?php echo $d['medico']; ?></td>
                    <td class="p-6 text-right font-black text-blue-600 text-lg"><?php echo $d['v_disp']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    aside, header, .no-print { display: none !important; }
    body { background: white !important; }
    .hidden.md\:block { display: block !important; }
    .rounded-[50px] { border-radius: 0 !important; }
}
</style>
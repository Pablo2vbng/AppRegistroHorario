<?php
$userId = $_SESSION['usuario_id'];
$mes = date('m'); $anio = date('Y');

// 1. Obtener la jornada base del empleado
$stmtU = $pdo->prepare("SELECT horas_jornada, dias_laborables FROM usuarios WHERE id = ?");
$stmtU->execute([$userId]);
$userDatos = $stmtU->fetch(PDO::FETCH_ASSOC);
$horasJornadaBase = $userDatos ? (float)$userDatos['horas_jornada'] : 8;
$diasLaborables = $userDatos && $userDatos['dias_laborables'] ? explode(',', $userDatos['dias_laborables']) : [1,2,3,4,5];

$stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
$stmt->execute([$userId, $mes, $anio]);
$fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jornadas = [];
foreach ($fichajes as $f) {
    $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
    $jornadas[$fecha][] = $f;
}
?>

<div class="max-w-5xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 no-print gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Mi Jornada Efectiva</h1>
            <p class="text-slate-400 font-bold text-sm">Registro de tiempos y balance</p>
        </div>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl">
            <i class="fas fa-print mr-2"></i> Imprimir Registro
        </button>
    </div>

    <div class="bg-white rounded-[45px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <tr>
                    <th class="p-6">Fecha</th>
                    <th class="p-6">Registros</th>
                    <th class="p-6 text-center">Horas Netas</th>
                    <th class="p-6 text-right">Balance Diario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php 
                foreach(array_reverse($jornadas) as $fecha => $eventos): 
                    $segundosTotales = 0;
                    $inicioTramo = null;

                    // Lógica para calcular solo tiempo efectivo
                    foreach($eventos as $ev) {
                        if($ev['tipo'] == 'entrada' || $ev['tipo'] == 'reanudar') {
                            $inicioTramo = strtotime($ev['fecha_hora']);
                        }
                        if(($ev['tipo'] == 'pausa' || $ev['tipo'] == 'salida') && $inicioTramo) {
                            $segundosTotales += (strtotime($ev['fecha_hora']) - $inicioTramo);
                            $inicioTramo = null;
                        }
                    }
                    
                    $horasRealesFloat = $segundosTotales / 3600;
                    $h = floor($segundosTotales/3600);
                    $m = floor(($segundosTotales/60)%60);

                    // --- CÁLCULO DE OBJETIVO Y PERMISOS ---
                    $diaSemana = date('N', strtotime($fecha));
                    $objetivoHoy = in_array($diaSemana, $diasLaborables) ? $horasJornadaBase : 0;

                    // Comprobar festivos
                    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ?");
                    $stF->execute([$fecha]);
                    if ($stF->fetch()) { $objetivoHoy = 0; }

                    // Comprobar si hay bajas médicas o vacaciones aprobadas que resten exigencia de horas
                    $stA = $pdo->prepare("SELECT es_por_horas, horas_solicitadas FROM ausencias WHERE usuario_id = ? AND estado = 'aprobado' AND ? BETWEEN fecha_inicio AND fecha_fin");
                    $stA->execute([$userId, $fecha]);
                    $ausencias = $stA->fetchAll();

                    foreach($ausencias as $aus) {
                        if ($aus['es_por_horas']) {
                            $objetivoHoy -= $aus['horas_solicitadas'];
                        } else {
                            $objetivoHoy = 0; // Día entero perdonado (por vacaciones o médico)
                        }
                    }
                    $objetivoHoy = max(0, $objetivoHoy);

                    // Calcular el balance (+ o -)
                    $balanceFloat = $horasRealesFloat - $objetivoHoy;
                    $signoBalance = ($balanceFloat >= 0) ? '+' : '-';
                    $colorBalance = ($balanceFloat >= 0) ? 'text-emerald-500' : 'text-rose-500';
                    $bgBalance = ($balanceFloat >= 0) ? 'bg-emerald-50' : 'bg-rose-50';
                    
                    $hBal = floor(abs($balanceFloat));
                    $mBal = round((abs($balanceFloat) - $hBal) * 60);
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-black text-slate-700"><?php echo date('d M', strtotime($fecha)); ?></td>
                    <td class="p-6">
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($eventos as $ev): 
                                $color = ($ev['tipo'] == 'entrada') ? 'text-emerald-500' : (($ev['tipo'] == 'pausa') ? 'text-amber-500' : 'text-slate-400');
                            ?>
                                <span class="text-[10px] font-black uppercase <?php echo $color; ?>">
                                    <?php echo date('H:i', strtotime($ev['fecha_hora'])); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="p-6 text-center">
                        <span class="text-lg font-black text-slate-800"><?php echo $h; ?>h <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>m</span>
                    </td>
                    <td class="p-6 text-right">
                        <?php if ($objetivoHoy > 0 || $balanceFloat != 0): ?>
                            <span class="px-3 py-1 rounded-lg text-sm font-black <?php echo $bgBalance . ' ' . $colorBalance; ?>">
                                <?php echo $signoBalance . $hBal . 'h ' . str_pad($mBal, 2, '0', STR_PAD_LEFT) . 'm'; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-slate-300 font-bold uppercase tracking-widest">Día Libre</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($jornadas)): ?>
                    <tr><td colspan="4" class="p-20 text-center text-slate-300 font-bold italic">Sin actividad este mes</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
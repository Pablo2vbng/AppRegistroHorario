<?php
$userId = $_SESSION['usuario_id'];
$mes = date('m'); $anio = date('Y');

$stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
$stmt->execute([$userId, $mes, $anio]);
$fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jornadas = [];
foreach ($fichajes as $f) {
    $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
    $jornadas[$fecha][] = $f;
}
?>

<div class="max-w-4xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 no-print gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Mi Jornada Efectiva</h1>
            <p class="text-slate-400 font-bold text-sm">Registro de tiempos en Benigànim</p>
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
                    <th class="p-6">Registros del día</th>
                    <th class="p-6 text-right">Horas Netas</th>
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
                    
                    $h = floor($segundosTotales/3600);
                    $m = floor(($segundosTotales/60)%60);
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
                    <td class="p-6 text-right">
                        <span class="text-lg font-black text-slate-800"><?php echo $h; ?>h <?php echo $m; ?>m</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($jornadas)): ?>
                    <tr><td colspan="3" class="p-20 text-center text-slate-300 font-bold italic">Sin actividad este mes</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
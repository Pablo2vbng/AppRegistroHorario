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
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Mi Registro Mensual</h1>
            <p class="text-slate-400 font-bold text-sm"><?php echo EMPRESA_SEDE; ?></p>
        </div>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black uppercase text-xs shadow-xl tracking-widest">
            <i class="fas fa-download mr-2"></i> Descargar Registro
        </button>
    </div>

    <!-- DOCUMENTO ESTILO LEGAL -->
    <div class="bg-white p-8 md:p-12 rounded-[40px] shadow-sm border border-slate-200 print:shadow-none print:border-none print:p-0">
        <div class="flex justify-between items-center border-b pb-6 mb-8">
            <div>
                <img src="assets/img/logoCvTools.jpg" class="h-10 rounded mb-2">
                <p class="text-[10px] font-black uppercase text-slate-400">Sede: <?php echo EMPRESA_SEDE; ?></p>
            </div>
            <div class="text-right">
                <p class="font-black text-slate-800 uppercase italic">Registro de Jornada</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $_SESSION['nombre']; ?></p>
            </div>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest">
                <tr>
                    <th class="p-4 border-b">Día</th>
                    <th class="p-4 border-b">Entrada</th>
                    <th class="p-4 border-b">Salida</th>
                    <th class="p-4 border-b text-right">Horas</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach($jornadas as $fecha => $eventos): 
                    $e = date('H:i', strtotime($eventos[0]['fecha_hora']));
                    $last = end($eventos);
                    $s = ($last['tipo'] == 'salida') ? date('H:i', strtotime($last['fecha_hora'])) : '---';
                    $sec = ($last['tipo'] == 'salida') ? strtotime($last['fecha_hora']) - strtotime($eventos[0]['fecha_hora']) : 0;
                ?>
                <tr>
                    <td class="p-4 font-bold text-slate-700"><?php echo date('d/m', strtotime($fecha)); ?></td>
                    <td class="p-4 text-emerald-600 font-mono"><?php echo $e; ?></td>
                    <td class="p-4 text-rose-500 font-mono"><?php echo $s; ?></td>
                    <td class="p-4 text-right font-black"><?php echo ($sec > 0) ? floor($sec/3600).'h '.floor(($sec/60)%60).'m' : '---'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-20 grid grid-cols-2 gap-10 opacity-0 print:opacity-100">
            <div class="border-t border-slate-300 pt-4 text-center uppercase text-[10px] font-black">Firma Trabajador</div>
            <div class="border-t border-slate-300 pt-4 text-center uppercase text-[10px] font-black">Firma Empresa</div>
        </div>
    </div>
</div>

<style>
@media print {
    aside, header, .no-print { display: none !important; }
    body { background: white !important; }
}
</style>
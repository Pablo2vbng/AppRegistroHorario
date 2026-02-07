<?php
if ($_SESSION['rol'] != 'admin' && $_GET['user_id'] != $_SESSION['usuario_id']) exit();
$userId = $_GET['user_id'] ?? '';
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll();

$datos = []; $usuario_nombre = "";
if ($userId) {
    $stmtU = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtU->execute([$userId]);
    $usuario_nombre = $stmtU->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fichajes as $f) { $fecha = date('Y-m-d', strtotime($f['fecha_hora'])); $datos[$fecha][] = $f; }
}
?>

<div class="max-w-4xl mx-auto pb-20">
    <div class="no-print bg-slate-900 text-white p-8 rounded-[40px] mb-10 shadow-2xl flex justify-between items-center">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1 italic">Registro Oficial de Jornada</p>
            <h1 class="text-2xl font-black italic uppercase tracking-tighter">Listado Mensual</h1>
        </div>
        <button onclick="window.print()" class="bg-blue-600 px-8 py-3 rounded-2xl font-black text-xs uppercase shadow-lg">Imprimir PDF</button>
    </div>

    <!-- FILTROS -->
    <div class="bg-white p-6 rounded-[35px] border border-slate-200 mb-10 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="p" value="informe_legal">
            <select name="user_id" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs" required>
                <option value="">-- Empleado --</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="mes" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
                <?php for($m=1;$m<=12;$m++): $m2=str_pad($m,2,'0',STR_PAD_LEFT); ?>
                    <option value="<?php echo $m2; ?>" <?php echo ($mes==$m2)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
            <button type="submit" class="bg-slate-800 text-white p-4 rounded-2xl font-black uppercase text-[10px]">Generar</button>
        </form>
    </div>

    <?php if ($userId): ?>
    <!-- DOCUMENTO OFICIAL -->
    <div class="bg-white p-12 border border-slate-200 print:border-none print:p-0">
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-8 mb-10">
            <div>
                <h2 class="text-2xl font-black uppercase italic tracking-tighter">Registro Diario de Jornada</h2>
                <p class="text-[10px] font-black text-slate-400 uppercase">Benigànim • Cumplimiento RD-Ley 8/2019</p>
            </div>
            <img src="<?php echo EMPRESA_LOGO; ?>" class="h-10 rounded">
        </div>

        <div class="grid grid-cols-2 gap-10 mb-10 text-[11px] uppercase font-bold">
            <div class="bg-slate-50 p-6 rounded-3xl">
                <p class="text-[9px] text-slate-400 mb-1">Empresa</p>
                <p class="text-slate-800"><?php echo EMPRESA_NOMBRE; ?> (<?php echo EMPRESA_CIF; ?>)</p>
                <p class="text-slate-500 font-normal mt-1 italic"><?php echo EMPRESA_DIRECCION; ?></p>
            </div>
            <div class="text-right p-6">
                <p class="text-[9px] text-slate-400 mb-1">Trabajador/a</p>
                <p class="text-slate-800"><?php echo $usuario_nombre; ?></p>
                <p class="text-blue-600 mt-1 italic">Mes: <?php echo date('F Y', mktime(0,0,0,$mes,1,$anio)); ?></p>
            </div>
        </div>

        <table class="w-full text-left border-collapse border border-slate-900">
            <thead>
                <tr class="bg-slate-900 text-white text-[8px] font-black uppercase tracking-widest">
                    <th class="p-2 border border-slate-900">Día</th>
                    <th class="p-2 border border-slate-900">Entrada</th>
                    <th class="p-2 border border-slate-900">Salida</th>
                    <th class="p-2 border border-slate-900 text-center">GPS OK</th>
                    <th class="p-2 border border-slate-900 text-right">Horas</th>
                </tr>
            </thead>
            <tbody class="text-[10px] font-bold">
                <?php 
                $diasEnMes = date('t', mktime(0,0,0,$mes,1,$anio));
                $totalS = 0;
                for($d=1; $d<=$diasEnMes; $d++):
                    $fecha = "$anio-$mes-".str_pad($d, 2, '0', STR_PAD_LEFT);
                    $h_e = '---'; $h_s = '---'; $h_t = '00:00'; $gps = '---';
                    if(isset($datos[$fecha])) {
                        $evs = $datos[$fecha];
                        $h_e = date('H:i', strtotime($evs[0]['fecha_hora']));
                        $last = end($evs);
                        if($last['tipo'] == 'salida') {
                            $h_s = date('H:i', strtotime($last['fecha_hora']));
                            $gps = $evs[0]['latitud'] ? 'SÍ' : 'NO';
                            $sec = 0; $start = null;
                            foreach($evs as $v) {
                                if($v['tipo']=='entrada' || $v['tipo']=='reanudar') $start = strtotime($v['fecha_hora']);
                                if(($v['tipo']=='pausa' || $v['tipo']=='salida') && $start) { $sec += (strtotime($v['fecha_hora']) - $start); $start = null; }
                            }
                            $totalS += $sec;
                            $h_t = floor($sec/3600).":".str_pad(floor(($sec/60)%60), 2, '0', STR_PAD_LEFT);
                        }
                    }
                ?>
                <tr class="<?php echo isset($datos[$fecha])?'bg-white':'bg-slate-50 text-slate-300'; ?>">
                    <td class="p-1 border border-slate-200"><?php echo $d; ?></td>
                    <td class="p-1 border border-slate-200"><?php echo $h_e; ?></td>
                    <td class="p-1 border border-slate-200"><?php echo $h_s; ?></td>
                    <td class="p-1 border border-slate-200 text-center font-black <?php echo ($gps=='SÍ')?'text-emerald-600':''; ?>"><?php echo $gps; ?></td>
                    <td class="p-1 border border-slate-200 text-right font-black"><?php echo $h_t; ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-black">
                    <td colspan="4" class="p-4 border border-slate-900 text-right uppercase text-[9px]">Cómputo Total del Mes</td>
                    <td class="p-4 border border-slate-900 text-right text-sm"><?php echo floor($totalS/3600); ?>h <?php echo floor(($totalS/60)%60); ?>m</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-20 grid grid-cols-2 gap-20">
            <div class="border-t border-slate-400 pt-4 text-center">
                <p class="text-[9px] font-black uppercase text-slate-400">Sello de Empresa / Responsable</p>
                <p class="text-[8px] italic mt-1">(Carmen - CV TOOLS S.L.)</p>
            </div>
            <div class="border-t border-slate-400 pt-4 text-center">
                <p class="text-[9px] font-black uppercase text-slate-400">Firma del Trabajador/a</p>
                <p class="text-[8px] italic mt-1">(Aceptación de tiempos y ubicación)</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
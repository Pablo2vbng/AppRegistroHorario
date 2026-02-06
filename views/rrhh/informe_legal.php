<?php
// Parámetros de filtrado
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Obtener lista de usuarios para el filtro
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll();

$datos = [];
$usuario_nombre = "";

if ($userId) {
    // Info del usuario
    $stmtU = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtU->execute([$userId]);
    $usuario_nombre = $stmtU->fetchColumn();

    // Obtener fichajes
    $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fichajes as $f) {
        $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
        $datos[$fecha][] = $f;
    }
}
?>

<div class="no-print bg-amber-50 border-l-4 border-amber-400 p-4 mb-8 rounded-r-xl">
    <p class="text-amber-800 text-sm font-bold">
        <i class="fas fa-info-circle mr-2"></i> Este documento es el <strong>Registro Mensual de Jornada</strong> obligatorio según el RD-ley 8/2019. 
        Filtra por empleado y pulsa "Imprimir" para generar el PDF legal.
    </p>
</div>

<!-- FILTROS (No se imprimen) -->
<div class="bg-white p-6 rounded-2xl shadow-sm border mb-10 no-print">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="p" value="informe_legal">
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Empleado</label>
            <select name="user_id" class="w-full bg-slate-50 border p-3 rounded-xl font-bold" required>
                <option value="">-- Seleccionar --</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Mes</label>
            <select name="mes" class="w-full bg-slate-50 border p-3 rounded-xl font-bold">
                <?php for($m=1; $m<=12; $m++): $m2 = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?php echo $m2; ?>" <?php echo ($mes == $m2)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Año</label>
            <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-3 rounded-xl font-bold">
        </div>
        <button type="submit" class="bg-blue-600 text-white p-3 rounded-xl font-bold uppercase text-xs tracking-widest">Generar</button>
    </form>
</div>

<?php if ($userId): ?>
<!-- DOCUMENTO LEGAL (Se ve bien en pantalla e imprime perfecto) -->
<div class="bg-white p-10 shadow-2xl rounded-sm border border-slate-200 max-w-4xl mx-auto print:shadow-none print:border-none print:p-0">
    <div class="flex justify-between items-start border-b-2 border-slate-900 pb-6 mb-8">
        <div>
            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Registro Diario de Jornada</h2>
            <p class="text-xs font-bold text-slate-500 uppercase">Cumplimiento RD-Ley 8/2019 de 8 de Marzo</p>
        </div>
        <img src="assets/img/logoCvTools.jpg" class="h-12 rounded">
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
        <div>
            <p class="font-black text-slate-400 uppercase text-[10px]">Empresa</p>
            <p class="font-bold">CV TOOLS S.L.</p>
            <p class="text-slate-500 italic">CIF: B-00000000 (Edita esto en informes_legal.php)</p>
        </div>
        <div>
            <p class="font-black text-slate-400 uppercase text-[10px]">Trabajador/a</p>
            <p class="font-bold"><?php echo strtoupper($usuario_nombre); ?></p>
            <p class="text-slate-500 italic">Mes: <?php echo date('F Y', mktime(0,0,0,$mes,1,$anio)); ?></p>
        </div>
    </div>

    <table class="w-full text-left border-collapse mb-10">
        <thead>
            <tr class="bg-slate-900 text-white text-[10px] uppercase">
                <th class="p-2 border border-slate-900">Día</th>
                <th class="p-2 border border-slate-900">Entrada</th>
                <th class="p-2 border border-slate-900">Salida</th>
                <th class="p-2 border border-slate-900">Pausas</th>
                <th class="p-2 border border-slate-900 text-right">Total Horas</th>
            </tr>
        </thead>
        <tbody class="text-xs">
            <?php 
            $diasEnMes = date('t', mktime(0,0,0,$mes,1,$anio));
            $totalMesSegundos = 0;

            for($d=1; $d<=$diasEnMes; $d++):
                $fecha = "$anio-$mes-".str_pad($d, 2, '0', STR_PAD_LEFT);
                $hayFichaje = isset($datos[$fecha]);
                
                $h_e = '---'; $h_s = '---'; $h_t = '00:00';
                
                if($hayFichaje) {
                    $eventos = $datos[$fecha];
                    $h_e = date('H:i', strtotime($eventos[0]['fecha_hora']));
                    $last = end($eventos);
                    if($last['tipo'] == 'salida') {
                        $h_s = date('H:i', strtotime($last['fecha_hora']));
                        $segundos = strtotime($last['fecha_hora']) - strtotime($eventos[0]['fecha_hora']);
                        $totalMesSegundos += $segundos;
                        $h_t = floor($segundos/3600).":".str_pad(floor(($segundos/60)%60), 2, '0', STR_PAD_LEFT);
                    }
                }
            ?>
            <tr class="<?php echo ($hayFichaje)?'bg-white':'bg-slate-50 text-slate-300'; ?>">
                <td class="p-1.5 border border-slate-200 font-bold"><?php echo $d; ?></td>
                <td class="p-1.5 border border-slate-200"><?php echo $h_e; ?></td>
                <td class="p-1.5 border border-slate-200"><?php echo $h_s; ?></td>
                <td class="p-1.5 border border-slate-200">---</td>
                <td class="p-1.5 border border-slate-200 text-right font-bold"><?php echo $h_t; ?></td>
            </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-black">
                <td colspan="4" class="p-3 border border-slate-300 text-right uppercase text-[10px]">Total Horas Mensuales</td>
                <td class="p-3 border border-slate-300 text-right text-lg">
                    <?php echo floor($totalMesSegundos/3600); ?>h <?php echo floor(($totalMesSegundos/60)%60); ?>m
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="grid grid-cols-2 gap-20 mt-20 no-print-flex">
        <div class="border-t border-slate-400 pt-4 text-center">
            <p class="text-[10px] font-black uppercase text-slate-400">Firma de la Empresa</p>
        </div>
        <div class="border-t border-slate-400 pt-4 text-center">
            <p class="text-[10px] font-black uppercase text-slate-400">Firma del Trabajador/a</p>
        </div>
    </div>

    <div class="mt-10 no-print">
        <button onclick="window.print()" class="w-full bg-emerald-600 text-white font-black py-4 rounded-xl shadow-lg hover:bg-emerald-700 transition">
            <i class="fas fa-print mr-2"></i> IMPRIMIR REGISTRO LEGAL
        </button>
    </div>
</div>
<?php endif; ?>
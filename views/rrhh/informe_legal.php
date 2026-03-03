<?php
// Seguridad: Solo admin o el propio usuario
if ($_SESSION['rol'] != 'admin' && (isset($_GET['user_id']) && $_GET['user_id'] != $_SESSION['usuario_id'])) {
    exit("Acceso denegado");
}

$userId = $_GET['user_id'] ?? '';
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

// Filtro: Solo empleados reales (Usamos Prepared Statement para seguridad)
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();

$datos = []; 
$usuario_nombre = "";

if ($userId) {
    $stmtU = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtU->execute([$userId]);
    $usuario_nombre = $stmtU->fetchColumn();

    // Consulta de fichajes optimizada
    $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? ORDER BY fecha_hora ASC");
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fichajes as $f) { 
        $fecha = date('Y-m-d', strtotime($f['fecha_hora'])); 
        $datos[$fecha][] = $f; 
    }
}
?>

<div class="max-w-4xl mx-auto pb-20">
    <div class="no-print bg-slate-900 text-white p-6 rounded-[35px] mb-8 shadow-xl flex justify-between items-center mx-2">
        <h1 class="text-lg font-black uppercase italic tracking-tighter">Listado Mensual</h1>
        <button onclick="window.print()" class="bg-blue-600 px-5 py-2 rounded-xl font-black text-[10px] uppercase">Descargar PDF / Imprimir</button>
    </div>

    <?php if($_SESSION['rol'] == 'admin'): ?>
    <div class="bg-white p-6 rounded-[35px] border border-slate-200 mb-10 no-print shadow-sm mx-2">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="p" value="informe_legal">
            <select name="user_id" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs" required>
                <option value="">-- Elegir Empleado --</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="mes" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
                <?php 
                $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                foreach($meses as $idx => $nombreM): 
                    $m2 = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
                ?>
                    <option value="<?php echo $m2; ?>" <?php echo ($mes == $m2)?'selected':''; ?>><?php echo $nombreM; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold text-xs">
            <button type="submit" class="bg-slate-800 text-white p-4 rounded-2xl font-black uppercase text-[10px]">Generar Informe</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($userId && $usuario_nombre): ?>
    <div class="bg-white p-6 md:p-12 border border-slate-200 print:border-none print:p-0 mx-2 shadow-sm rounded-[40px] print:rounded-none">
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-6 mb-8">
            <div>
                <h2 class="text-xl font-black uppercase italic tracking-tighter">Registro de Jornada</h2>
                <p class="text-[9px] font-black text-slate-400 uppercase">Benigànim • Cumplimiento RD-Ley 8/2019</p>
            </div>
            <img src="<?php echo EMPRESA_LOGO; ?>" class="h-8 rounded">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10 text-[10px] uppercase font-bold">
            <div class="bg-slate-50 p-5 rounded-3xl">
                <p class="text-[8px] text-slate-400 mb-1">Empresa</p>
                <p class="text-slate-800"><?php echo EMPRESA_NOMBRE; ?> (<?php echo EMPRESA_CIF; ?>)</p>
            </div>
            <div class="md:text-right p-5">
                <p class="text-[8px] text-slate-400 mb-1">Trabajador/a</p>
                <p class="text-slate-800 font-black text-xs"><?php echo $usuario_nombre; ?></p>
                <p class="text-blue-600 mt-1 italic"><?php echo $mes . " / " . $anio; ?></p>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-900 rounded-xl">
            <table class="w-full text-left border-collapse min-w-[500px]">
                <thead>
                    <tr class="bg-slate-900 text-white text-[8px] font-black uppercase">
                        <th class="p-2 border border-slate-900">Día</th>
                        <th class="p-2 border border-slate-900">Entrada</th>
                        <th class="p-2 border border-slate-900">Salida</th>
                        <th class="p-2 border border-slate-900 text-center">Ubicación (GPS)</th>
                        <th class="p-2 border border-slate-900 text-right">Total Horas</th>
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
                                // Verificamos si hay coordenadas en la entrada o salida
                                $gps = ($evs[0]['latitud'] || $last['latitud']) ? 'REGISTRADO' : 'NO DISPONIBLE';
                                
                                $sec = 0; $start = null;
                                foreach($evs as $v) {
                                    if($v['tipo']=='entrada' || $v['tipo']=='reanudar') $start = strtotime($v['fecha_hora']);
                                    if(($v['tipo']=='pausa' || $v['tipo']=='salida') && $start) { 
                                        $sec += (strtotime($v['fecha_hora']) - $start); 
                                        $start = null; 
                                    }
                                }
                                $totalS += $sec; 
                                $h_t = sprintf('%02d:%02d', floor($sec/3600), floor(($sec/60)%60));
                            }
                        }
                    ?>
                    <tr class="<?php echo isset($datos[$fecha])?'bg-white':'bg-slate-50 text-slate-300'; ?>">
                        <td class="p-1.5 border border-slate-200"><?php echo $d; ?></td>
                        <td class="p-1.5 border border-slate-200"><?php echo $h_e; ?></td>
                        <td class="p-1.5 border border-slate-200"><?php echo $h_s; ?></td>
                        <td class="p-1.5 border border-slate-200 text-center text-[8px]"><?php echo $gps; ?></td>
                        <td class="p-1.5 border border-slate-200 text-right font-black"><?php echo $h_t; ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-black text-xs">
                        <td colspan="4" class="p-3 border border-slate-900 text-right uppercase">Cómputo Total Mensual</td>
                        <td class="p-3 border border-slate-900 text-right text-blue-700"><?php echo floor($totalS/3600); ?>h <?php echo floor(($totalS/60)%60); ?>m</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="mt-12 grid grid-cols-2 gap-10 text-center">
            <div class="border-t border-slate-300 pt-4">
                <p class="text-[8px] font-black uppercase text-slate-400">Firma Empresa</p>
            </div>
            <div class="border-t border-slate-300 pt-4">
                <p class="text-[8px] font-black uppercase text-slate-400">Firma Trabajador/a</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
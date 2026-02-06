<?php
// 1. Obtener lista de empleados para el desplegable
$stmtUsers = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
$usuarios = $stmtUsers->fetchAll();

// 2. Parámetros de filtrado
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$jornadas = [];

if ($userId) {
    // 3. Obtener fichajes del empleado filtrado
    $sql = "SELECT * FROM fichajes 
            WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ? 
            ORDER BY fecha_hora ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $mes, $anio]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por día
    foreach ($fichajes as $f) {
        $fecha = date('Y-m-d', strtotime($f['fecha_hora']));
        $jornadas[$fecha][] = $f;
    }
}
?>

<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 no-print">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic">Control de Jornadas</h1>
        <button onclick="window.print()" class="bg-slate-800 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Generar Informe Legal
        </button>
    </div>

    <!-- FILTROS (Se ocultan al imprimir) -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border mb-8 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="p" value="informes_equipo">
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Seleccionar Empleado</label>
                <select name="user_id" class="w-full bg-slate-50 border p-3 rounded-xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Elige empleado --</option>
                    <?php foreach($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($userId == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo $u['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Mes</label>
                <select name="mes" class="w-full bg-slate-50 border p-3 rounded-xl font-bold">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo str_pad($i, 2, "0", STR_PAD_LEFT); ?>" <?php echo ($mes == $i) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Año</label>
                <input type="number" name="anio" value="<?php echo $anio; ?>" class="w-full bg-slate-50 border p-3 rounded-xl font-bold">
            </div>

            <button type="submit" class="bg-blue-600 text-white p-3 rounded-xl font-bold hover:bg-blue-700 transition uppercase text-xs tracking-widest">
                <i class="fas fa-search mr-2"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- RESULTADOS (Listado para RRHH) -->
    <div class="bg-white rounded-3xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[700px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase">Fecha</th>
                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase">Entrada</th>
                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase">Salida</th>
                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase text-right">Horas Efectivas</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($jornadas)): ?>
                        <tr><td colspan="4" class="p-10 text-center text-slate-400 italic">No hay registros para este filtro.</td></tr>
                    <?php else: ?>
                        <?php foreach($jornadas as $fecha => $eventos): 
                            $entrada = $eventos[0]['fecha_hora'];
                            $salida = end($eventos);
                            $hora_e = date('H:i', strtotime($entrada));
                            $hora_s = ($salida['tipo'] == 'salida') ? date('H:i', strtotime($salida['fecha_hora'])) : 'Fichaje abierto';
                            
                            $segundos = 0;
                            if($salida['tipo'] == 'salida') {
                                $segundos = strtotime($salida['fecha_hora']) - strtotime($entrada);
                            }
                            $h = floor($segundos / 3600);
                            $m = floor(($segundos / 60) % 60);
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-5 font-bold text-slate-700"><?php echo date('d/m/Y', strtotime($fecha)); ?></td>
                            <td class="p-5 text-emerald-600 font-mono font-bold"><?php echo $hora_e; ?></td>
                            <td class="p-5 text-rose-500 font-mono font-bold"><?php echo $hora_s; ?></td>
                            <td class="p-5 text-right font-black text-slate-800 text-lg">
                                <?php echo ($segundos > 0) ? "{$h}h {$m}m" : "---"; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, aside, header { display: none !important; }
        body { background: white; }
        .bg-white { border: none !important; shadow: none !important; }
    }
</style>
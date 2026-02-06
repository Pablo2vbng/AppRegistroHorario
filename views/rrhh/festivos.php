<?php
if ($_SESSION['rol'] != 'admin') exit();

// Procesar cambio de festivo a laborable
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $stmt = $pdo->prepare("SELECT laborable_canjeado FROM festivos WHERE id = ?");
    $stmt->execute([$id]);
    $actual = $stmt->fetchColumn();
    $nuevoEstado = $actual ? 0 : 1;

    $stmtU = $pdo->prepare("UPDATE festivos SET laborable_canjeado = ? WHERE id = ?");
    $stmtU->execute([$nuevoEstado, $id]);

    $operacion = $nuevoEstado ? "+ 1" : "- 1";
    $pdo->query("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles $operacion, dias_vacaciones_totales = dias_vacaciones_totales $operacion");
    
    header("Location: index.php?p=gestion_festivos"); exit();
}

$festivos = $pdo->query("SELECT * FROM festivos ORDER BY fecha ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto pb-20">
    <div class="mb-8 px-2">
        <h1 class="text-2xl md:text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Festivos Benigànim</h1>
        <p class="text-slate-500 font-bold text-sm">Configuración de días no laborables</p>
    </div>

    <!-- AVISO LÓGICA -->
    <div class="bg-blue-600 text-white p-6 rounded-[30px] mb-8 shadow-xl shadow-blue-100 mx-2">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-info text-sm"></i>
            </div>
            <p class="text-[11px] font-bold uppercase tracking-widest leading-relaxed">
                Si marcas un festivo como <span class="underline">Trabajado</span>, sumaremos automáticamente 1 día a la bolsa de vacaciones de todo el equipo.
            </p>
        </div>
    </div>

    <!-- VISTA MÓVIL (TARJETAS) -->
    <div class="grid grid-cols-1 gap-4 md:hidden px-2">
        <?php foreach($festivos as $f): 
            $esCierre = $f['descuenta_vacaciones'];
            $statusClass = $f['laborable_canjeado'] ? 'bg-emerald-500' : ($esCierre ? 'bg-rose-500' : 'bg-slate-800');
        ?>
            <div class="bg-white p-6 rounded-[35px] shadow-sm border border-slate-200">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo date('d M Y', strtotime($f['fecha'])); ?></p>
                        <h3 class="font-black text-slate-800 uppercase text-sm mt-1"><?php echo $f['nombre']; ?></h3>
                    </div>
                    <span class="px-2 py-1 rounded-lg text-[8px] font-black uppercase text-white <?php echo $statusClass; ?>">
                        <?php echo $f['tipo']; ?>
                    </span>
                </div>
                
                <div class="pt-4 border-t border-slate-50">
                    <a href="index.php?p=gestion_festivos&toggle_id=<?php echo $f['id']; ?>" 
                       class="w-full flex items-center justify-center gap-3 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition shadow-md
                       <?php echo $f['laborable_canjeado'] ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-50 text-slate-400 border border-slate-100'; ?>">
                        <?php if($f['laborable_canjeado']): ?>
                            <i class="fas fa-check-circle"></i> Canjeado por Vacaciones
                        <?php else: ?>
                            <i class="fas fa-exchange-alt"></i> Marcar como Trabajado
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- VISTA DESKTOP (TABLA) -->
    <div class="hidden md:block bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 border-b">
                <tr>
                    <th class="p-6">Fecha</th>
                    <th class="p-6">Festividad</th>
                    <th class="p-6">Tipo</th>
                    <th class="p-6 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($festivos as $f): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-bold text-slate-600 text-sm"><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
                    <td class="p-6 font-black text-slate-800 text-sm uppercase"><?php echo $f['nombre']; ?></td>
                    <td class="p-6">
                        <span class="px-2 py-1 bg-slate-100 rounded text-[9px] font-black uppercase text-slate-400 border border-slate-200">
                            <?php echo $f['tipo']; ?>
                        </span>
                    </td>
                    <td class="p-6 text-right">
                        <a href="index.php?p=gestion_festivos&toggle_id=<?php echo $f['id']; ?>" 
                           class="inline-block px-4 py-2 rounded-xl text-[9px] font-black uppercase border transition
                           <?php echo $f['laborable_canjeado'] ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-white text-slate-400 border-slate-200 hover:bg-slate-100'; ?>">
                            <?php echo $f['laborable_canjeado'] ? 'Trabajado (+1)' : 'Marcar Trabajado'; ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
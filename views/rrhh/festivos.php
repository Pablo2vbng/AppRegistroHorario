<?php
// Procesar cambio de festivo a laborable
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $stmt = $pdo->prepare("SELECT laborable_canjeado FROM festivos WHERE id = ?");
    $stmt->execute([$id]);
    $actual = $stmt->fetchColumn();
    $nuevoEstado = $actual ? 0 : 1;

    // Actualizamos el festivo
    $stmtU = $pdo->prepare("UPDATE festivos SET laborable_canjeado = ? WHERE id = ?");
    $stmtU->execute([$nuevoEstado, $id]);

    // Lógica: Si se marca como laborable, sumamos 1 día de vacaciones a TODOS los empleados
    $operacion = $nuevoEstado ? "+ 1" : "- 1";
    $pdo->query("UPDATE usuarios SET dias_vacaciones_disponibles = dias_vacaciones_disponibles $operacion, dias_vacaciones_totales = dias_vacaciones_totales $operacion");
    
    header("Location: index.php?p=gestion_festivos"); exit();
}

$festivos = $pdo->query("SELECT * FROM festivos ORDER BY fecha ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-10">
        <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Festivos Benigànim & Empresa</h1>
        <p class="text-slate-500 font-bold">Gestiona los días no laborables de CVTools</p>
    </div>

    <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    <th class="p-6">Fecha</th>
                    <th class="p-6">Motivo / Festividad</th>
                    <th class="p-6">Tipo</th>
                    <th class="p-6 text-right">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($festivos as $f): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-6 font-bold text-slate-600"><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
                    <td class="p-6">
                        <p class="font-black text-slate-800 text-sm"><?php echo $f['nombre']; ?></p>
                    </td>
                    <td class="p-6">
                        <span class="px-2 py-1 bg-slate-100 rounded text-[9px] font-black uppercase text-slate-400 border">
                            <?php echo $f['tipo']; ?>
                        </span>
                    </td>
                    <td class="p-6 text-right">
                        <?php if($f['laborable_canjeado']): ?>
                            <a href="index.php?p=gestion_festivos&toggle_id=<?php echo $f['id']; ?>" class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase border border-emerald-200">
                                <i class="fas fa-briefcase mr-1"></i> TRABAJADO (+1 vac.)
                            </a>
                        <?php else: ?>
                            <a href="index.php?p=gestion_festivos&toggle_id=<?php echo $f['id']; ?>" class="bg-rose-50 text-rose-500 px-4 py-2 rounded-xl text-[10px] font-black uppercase border border-rose-100">
                                <i class="fas fa-holly-berry mr-1"></i> FESTIVO
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-8 bg-blue-50 p-6 rounded-3xl border border-blue-100">
        <p class="text-blue-800 text-xs font-bold italic text-center">
            <i class="fas fa-info-circle mr-2"></i> Al marcar un festivo como "Trabajado", el sistema añadirá automáticamente 1 día de vacaciones a toda la plantilla.
        </p>
    </div>
</div>
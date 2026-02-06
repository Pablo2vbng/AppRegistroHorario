<?php
$emp_id = $_GET['id'];

// 1. Datos básicos
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$emp_id]);
$emp = $stmt->fetch();

if(!$emp) { echo "Empleado no encontrado"; exit(); }

// 2. Últimos 10 fichajes
$stmtF = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY fecha_hora DESC LIMIT 10");
$stmtF->execute([$emp_id]);
$fichajes = $stmtF->fetchAll();

// 3. Documentos
$stmtD = $pdo->prepare("SELECT * FROM documentos WHERE usuario_id = ? ORDER BY fecha_subida DESC");
$stmtD->execute([$emp_id]);
$documentos = $stmtD->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <!-- CABECERA PERFIL -->
    <div class="bg-white rounded-[40px] shadow-sm border p-8 md:p-12 mb-10 flex flex-col md:flex-row items-center gap-10">
        <div class="w-32 h-32 rounded-[32px] bg-slate-900 text-white flex items-center justify-center text-5xl font-black shadow-2xl border-4 border-white rotate-3">
            <?php echo substr($emp['nombre'], 0, 1); ?>
        </div>
        <div class="flex-1 text-center md:text-left">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-2">
                <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter"><?php echo $emp['nombre']; ?></h1>
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-200">Activo</span>
            </div>
            <p class="text-slate-400 font-bold mb-6 italic"><?php echo $emp['email']; ?> • Benigànim</p>
            
            <div class="flex flex-wrap justify-center md:justify-start gap-4">
                <div class="bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase leading-none mb-1">Horario Asignado</p>
                    <p class="font-black text-slate-800 text-sm italic uppercase"><?php echo $emp['horario'] ?: 'Flexible'; ?></p>
                </div>
                <div class="bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase leading-none mb-1">Vacaciones Disponibles</p>
                    <p class="font-black text-blue-600 text-sm italic"><?php echo $emp['dias_vacaciones_disponibles']; ?> Días</p>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <a href="index.php?p=informe_legal&user_id=<?php echo $emp_id; ?>" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase text-center shadow-lg">Informe Legal</a>
            <a href="https://wa.me/34687166120" target="_blank" class="bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase text-center shadow-lg"><i class="fab fa-whatsapp mr-2"></i> Mensaje</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- ÚLTIMOS FICHAJES -->
        <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic">Actividad Reciente</h3>
            </div>
            <div class="p-4">
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y">
                        <?php foreach($fichajes as $f): ?>
                            <tr>
                                <td class="p-4 font-bold text-slate-500"><?php echo date('d M', strtotime($f['fecha_hora'])); ?></td>
                                <td class="p-4 uppercase font-black text-[10px] tracking-widest">
                                    <span class="<?php echo ($f['tipo'] == 'entrada') ? 'text-emerald-500' : 'text-slate-400'; ?>">
                                        <?php echo $f['tipo']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-mono font-bold text-slate-800"><?php echo date('H:i', strtotime($f['fecha_hora'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- EXPEDIENTE DOCUMENTAL -->
        <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic">Expediente Digital</h3>
            </div>
            <div class="p-8 space-y-4">
                <?php foreach($documentos as $d): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-blue-300 transition">
                        <div class="flex items-center">
                            <i class="fas fa-file-pdf text-rose-500 text-xl mr-4"></i>
                            <div>
                                <p class="text-xs font-black text-slate-800 uppercase truncate w-32"><?php echo $d['nombre_archivo']; ?></p>
                                <p class="text-[9px] font-bold text-slate-400"><?php echo date('d/m/Y', strtotime($d['fecha_subida'])); ?></p>
                            </div>
                        </div>
                        <a href="<?php echo $d['ruta']; ?>" target="_blank" class="bg-white p-2 rounded-lg text-slate-400 hover:text-blue-600 shadow-sm border border-slate-100">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($documentos)) echo '<p class="text-slate-400 text-center italic py-10">Sin documentos subidos</p>'; ?>
            </div>
        </div>
    </div>
</div>
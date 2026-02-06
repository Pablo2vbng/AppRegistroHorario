<?php
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC");
$empleados = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h1 class="text-2xl font-black text-slate-800 italic uppercase">Gestión de Equipo</h1>
    <button onclick="toggleModal(true)" class="w-full md:w-auto bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg">
        <i class="fas fa-plus mr-2"></i> Nuevo Empleado
    </button>
</div>

<!-- Contenedor con scroll para móvil -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[600px]">
            <thead class="bg-slate-50 border-b">
                <tr>
                    <th class="p-4 text-xs font-black text-slate-400 uppercase">Nombre</th>
                    <th class="p-4 text-xs font-black text-slate-400 uppercase">Email</th>
                    <th class="p-4 text-xs font-black text-slate-400 uppercase">Rol</th>
                    <th class="p-4 text-xs font-black text-slate-400 text-right uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($empleados as $e): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4 font-bold text-slate-700"><?php echo $e['nombre']; ?></td>
                    <td class="p-4 text-slate-500 text-sm"><?php echo $e['email']; ?></td>
                    <td class="p-4">
                        <span class="text-[10px] font-black uppercase px-2 py-1 rounded <?php echo $e['rol']=='admin'?'bg-purple-100 text-purple-600':'bg-blue-100 text-blue-600';?>">
                            <?php echo $e['rol']; ?>
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <button class="text-slate-300 hover:text-blue-500 mx-2"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
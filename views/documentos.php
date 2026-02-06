<?php
$usuario_id = $_SESSION['usuario_id'];

// Si es admin y pide ver los de alguien concreto (lo haremos más adelante), de momento los suyos
$stmt = $pdo->prepare("SELECT * FROM documentos WHERE usuario_id = ? ORDER BY fecha_subida DESC");
$stmt->execute([$usuario_id]);
$docs = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-black text-slate-800 uppercase italic">Mis Documentos</h1>
        <button onclick="document.getElementById('file-upload').click()" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition">
            <i class="fas fa-upload mr-2"></i> Subir Documento
        </button>
        <input type="file" id="file-upload" class="hidden" onchange="subirArchivo(this)">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach($docs as $d): 
            $icon = ($d['tipo_doc'] == 'nomina') ? 'fa-file-invoice-dollar text-emerald-500' : 'fa-file-pdf text-rose-500';
        ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between group hover:border-blue-300 transition">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-2xl mr-4">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div>
                        <p class="font-bold text-slate-700 truncate w-40"><?php echo $d['nombre_archivo']; ?></p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase"><?php echo date('d M Y', strtotime($d['fecha_subida'])); ?></p>
                    </div>
                </div>
                <a href="<?php echo $d['ruta']; ?>" target="_blank" class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 hover:bg-blue-600 hover:text-white transition">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        <?php endforeach; ?>

        <?php if(empty($docs)): ?>
            <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                <i class="fas fa-folder-open text-slate-200 text-6xl mb-4"></i>
                <p class="text-slate-400 font-bold italic">No hay documentos subidos todavía.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function subirArchivo(input) {
    if (!input.files[0]) return;
    
    const formData = new FormData();
    formData.append('archivo', input.files[0]);

    fetch('api/documentos_subir.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) location.reload();
        else alert("Error: " + data.message);
    });
}
</script>
<?php
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-black text-slate-800 uppercase italic mb-8">Mi Perfil de Usuario</h1>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-10">
            <form id="formPerfil" class="space-y-6">
                <div class="flex items-center space-x-6 mb-8">
                    <div class="w-24 h-24 bg-slate-100 rounded-2xl flex items-center justify-center text-4xl text-slate-300 font-black border-2 border-dashed border-slate-200">
                        <?php echo substr($user['nombre'], 0, 1); ?>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800"><?php echo $user['nombre']; ?></h3>
                        <p class="text-xs font-bold text-slate-400 uppercase"><?php echo $user['rol']; ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Nombre Completo</label>
                        <input type="text" name="nombre" value="<?php echo $user['nombre']; ?>" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Email Corporativo</label>
                        <input type="email" value="<?php echo $user['email']; ?>" disabled class="w-full bg-slate-100 border p-4 rounded-2xl font-bold text-slate-400 cursor-not-allowed">
                    </div>
                </div>

                <hr class="border-slate-100">

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                    <input type="password" name="password" class="w-full bg-slate-50 border p-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-black py-5 rounded-2xl shadow-xl uppercase text-xs tracking-widest transition active:scale-95">
                    Guardar Cambios
                </button>
            </form>
            <div id="perfil-msg" class="mt-4 text-center text-xs font-bold"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('formPerfil').onsubmit = function(e) {
    e.preventDefault();
    const msg = document.getElementById('perfil-msg');
    msg.innerText = "Actualizando datos...";
    
    fetch('api/perfil_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            msg.className = "mt-4 text-center text-emerald-500 text-xs font-bold";
            msg.innerText = "¡Perfil actualizado con éxito!";
            setTimeout(() => location.reload(), 1500);
        } else {
            msg.className = "mt-4 text-center text-rose-500 text-xs font-bold";
            msg.innerText = "Error: " + data.message;
        }
    });
}
</script>
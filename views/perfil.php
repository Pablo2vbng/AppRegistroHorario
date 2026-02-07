<?php
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$u = $stmt->fetch();
?>

<div class="max-w-2xl mx-auto pb-20">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter mb-2">Mi Perfil CVTools</h1>
        <p class="text-slate-400 font-bold text-sm">Gestiona tus datos personales y de acceso</p>
    </div>

    <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-10 md:p-16">
            <form id="formPerfil" class="space-y-8">
                <div class="flex flex-col items-center mb-10">
                    <div class="w-32 h-32 rounded-[40px] bg-slate-900 text-white flex items-center justify-center text-5xl font-black shadow-2xl border-4 border-white rotate-3 mb-6">
                        <?php echo substr($u['nombre'], 0, 1); ?>
                    </div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.3em] bg-blue-50 px-4 py-1 rounded-full italic"><?php echo $u['rol']; ?></p>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest ml-4">Nombre Completo</label>
                        <input type="text" name="nombre" value="<?php echo $u['nombre']; ?>" required class="w-full bg-slate-50 border-2 border-slate-50 p-5 rounded-3xl font-black text-slate-700 outline-none focus:border-blue-500 transition shadow-inner">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest ml-4">Email Corporativo</label>
                        <input type="email" value="<?php echo $u['email']; ?>" disabled class="w-full bg-slate-100 border-2 border-slate-100 p-5 rounded-3xl font-black text-slate-300 cursor-not-allowed">
                        <p class="text-[9px] text-slate-300 mt-2 ml-4 uppercase italic">El email solo puede ser modificado por Carmen.</p>
                    </div>
                    <hr class="border-slate-50 my-4">
                    <div>
                        <label class="block text-[10px] font-black text-rose-400 uppercase mb-3 tracking-widest ml-4 italic">Cambiar Contraseña</label>
                        <input type="password" name="password" placeholder="Nueva contraseña (opcional)" class="w-full bg-rose-50/30 border-2 border-rose-50 p-5 rounded-3xl font-black text-slate-700 outline-none focus:border-rose-300 transition shadow-inner">
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-black py-6 rounded-3xl shadow-xl uppercase text-xs tracking-[0.2em] transition hover:bg-blue-600 active:scale-95">Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formPerfil').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/perfil_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: 'Perfil Actualizado', text: 'Tus cambios se han guardado correctamente.', confirmButtonColor: '#000' })
            .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}
</script>
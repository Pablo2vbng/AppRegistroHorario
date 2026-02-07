<?php
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$u = $stmt->fetch();

// Si no tiene foto, usamos el avatar de iniciales
$avatar = $u['foto_url'] ?: 'https://ui-avatars.com/api/?background=0f172a&color=fff&size=128&name='.urlencode($u['nombre']);
?>

<div class="max-w-4xl mx-auto pb-20">
    <!-- BOTÓN VOLVER -->
    <div class="mb-6 no-print">
        <a href="index.php?p=dashboard" class="text-slate-400 hover:text-slate-900 font-black text-[10px] uppercase tracking-widest flex items-center transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver al Escritorio
        </a>
    </div>

    <div class="bg-white rounded-[50px] shadow-2xl border border-slate-100 overflow-hidden">
        <form id="formPerfil" enctype="multipart/form-data">
            <!-- CABECERA PERFIL -->
            <div class="bg-slate-900 p-10 md:p-16 text-center relative overflow-hidden">
                <div class="relative z-10 flex flex-col items-center">
                    <div class="relative group">
                        <img id="img_preview" src="<?php echo $avatar; ?>" class="w-32 h-32 md:w-40 md:h-40 rounded-[40px] object-cover border-4 border-white shadow-2xl transition group-hover:opacity-80">
                        <label for="input_foto" class="absolute inset-0 flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition">
                            <div class="bg-black/50 text-white p-3 rounded-full"><i class="fas fa-camera"></i></div>
                        </label>
                        <input type="file" name="foto" id="input_foto" class="hidden" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <h2 class="text-white text-2xl font-black mt-6 uppercase italic tracking-tighter"><?php echo $u['nombre']; ?></h2>
                    <p class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.3em] mt-2 italic"><?php echo $u['rol']; ?> • Sede Benigànim</p>
                </div>
                <!-- Decoración -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
            </div>

            <!-- CUERPO DEL FORMULARIO -->
            <div class="p-8 md:p-16">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Datos Personales -->
                    <div class="space-y-6">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b pb-2">Información Personal</h3>
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-2 ml-2">Nombre Completo</label>
                            <input type="text" name="nombre" value="<?php echo $u['nombre']; ?>" required class="w-full bg-slate-50 border-2 border-slate-50 p-4 rounded-2xl font-bold text-slate-700 focus:border-blue-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-2 ml-2">Teléfono de Contacto</label>
                            <input type="tel" name="telefono" value="<?php echo $u['telefono']; ?>" placeholder="600 000 000" class="w-full bg-slate-50 border-2 border-slate-50 p-4 rounded-2xl font-bold text-slate-700 focus:border-blue-500 outline-none transition">
                        </div>
                    </div>

                    <!-- Localización -->
                    <div class="space-y-6">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b pb-2">Residencia</h3>
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-2 ml-2">Dirección</label>
                            <input type="text" name="direccion" value="<?php echo $u['direccion']; ?>" placeholder="Calle, número, piso..." class="w-full bg-slate-50 border-2 border-slate-50 p-4 rounded-2xl font-bold text-slate-700 focus:border-blue-500 outline-none transition">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-slate-500 uppercase mb-2 ml-2">Ciudad</label>
                                <input type="text" name="ciudad" value="<?php echo $u['ciudad']; ?>" placeholder="Benigànim" class="w-full bg-slate-50 border-2 border-slate-50 p-4 rounded-2xl font-bold text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-slate-500 uppercase mb-2 ml-2">C. Postal</label>
                                <input type="text" name="codigo_postal" value="<?php echo $u['codigo_postal']; ?>" placeholder="46830" class="w-full bg-slate-50 border-2 border-slate-50 p-4 rounded-2xl font-bold text-slate-700">
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad -->
                    <div class="md:col-span-2 mt-6 pt-6 border-t border-slate-50">
                        <div class="bg-rose-50 p-8 rounded-[35px] border border-rose-100">
                            <h3 class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] mb-4 italic">Zona de Seguridad</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[9px] font-black text-rose-400 uppercase mb-2 ml-2">Email (No editable)</label>
                                    <input type="email" value="<?php echo $u['email']; ?>" disabled class="w-full bg-white/50 border-2 border-rose-100 p-4 rounded-2xl font-bold text-rose-300 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-rose-400 uppercase mb-2 ml-2">Cambiar Contraseña</label>
                                    <input type="password" name="password" placeholder="Solo si deseas cambiarla" class="w-full bg-white border-2 border-rose-100 p-4 rounded-2xl font-bold text-slate-700 outline-none focus:border-rose-500 transition">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12">
                    <button type="submit" class="w-full bg-slate-900 text-white font-black py-6 rounded-[30px] shadow-2xl uppercase text-xs tracking-[0.3em] hover:bg-blue-600 transition-all active:scale-95">
                        <i class="fas fa-save mr-2"></i> Guardar mis cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Función para ver la foto antes de subirla
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('img_preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Envío del formulario por AJAX
document.getElementById('formPerfil').onsubmit = function(e) {
    e.preventDefault();
    Swal.fire({ title: 'Guardando...', text: 'Actualizando tu ficha en CVTools', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/perfil_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json()).then(data => {
        if(data.success) {
            Swal.fire({ icon: 'success', title: '¡Hecho!', text: 'Tus datos se han actualizado correctamente.', confirmButtonColor: '#0f172a' })
            .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}
</script>
<?php
require_once 'config/config.php';
$miId = $_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] == 'admin');

// 1. DATOS DEL USUARIO LOGUEADO
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$miId]);
$user = $stmtU->fetch();

// 2. LÓGICA DE PRODUCTIVIDAD
$mesActual = date('m'); $anioActual = date('Y'); $hoyDia = date('d');
$horasObjetivo = 0; $horasReales = 0;

$filtroEmp = $_GET['ver_emp'] ?? ($esAdmin ? 'all' : $miId);

try {
    if ($esAdmin && $filtroEmp == 'all') {
        // Cálculo para toda la plantilla
        $empleadosReal = $pdo->query("SELECT id, horas_jornada, dias_laborables FROM usuarios WHERE rol = 'empleado'")->fetchAll();
        foreach($empleadosReal as $emp) {
            $diasL = explode(',', $emp['dias_laborables']);
            for($d=1; $d<=$hoyDia; $d++) {
                $fechaE = "$anioActual-$mesActual-".str_pad($d, 2, '0', STR_PAD_LEFT);
                if(in_array(date('N', strtotime($fechaE)), $diasL)) {
                    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ? AND descuenta_vacaciones = 0");
                    $stF->execute([$fechaE]);
                    if(!$stF->fetch()) $horasObjetivo += $emp['horas_jornada'];
                }
            }
        }
        $stmtR = $pdo->prepare("SELECT f.fecha_hora, f.tipo FROM fichajes f JOIN usuarios u ON f.usuario_id = u.id WHERE MONTH(f.fecha_hora) = ? AND YEAR(f.fecha_hora) = ? AND u.rol = 'empleado'");
        $stmtR->execute([$mesActual, $anioActual]);
    } else {
        // Cálculo para un trabajador específico (Seguro con Prepared Statements)
        $idAConsultar = ($esAdmin) ? $filtroEmp : $miId;
        $stmtTarget = $pdo->prepare("SELECT horas_jornada, dias_laborables FROM usuarios WHERE id = ?");
        $stmtTarget->execute([$idAConsultar]);
        $target = $stmtTarget->fetch();
        
        if ($target) {
            $diasL = explode(',', $target['dias_laborables']);
            for($d=1; $d<=$hoyDia; $d++) {
                $fechaE = "$anioActual-$mesActual-".str_pad($d, 2, '0', STR_PAD_LEFT);
                if(in_array(date('N', strtotime($fechaE)), $diasL)) {
                    $stF = $pdo->prepare("SELECT id FROM festivos WHERE fecha = ? AND descuenta_vacaciones = 0");
                    $stF->execute([$fechaE]);
                    if(!$stF->fetch()) $horasObjetivo += $target['horas_jornada'];
                }
            }
            $stmtR = $pdo->prepare("SELECT fecha_hora, tipo FROM fichajes WHERE usuario_id = ? AND MONTH(fecha_hora) = ? AND YEAR(fecha_hora) = ?");
            $stmtR->execute([$idAConsultar, $mesActual, $anioActual]);
        }
    }

    $fichajesR = (isset($stmtR)) ? $stmtR->fetchAll() : [];
    $segundos = 0; $start = null;
    foreach($fichajesR as $f) {
        if($f['tipo']=='entrada' || $f['tipo']=='reanudar') $start = strtotime($f['fecha_hora']);
        if(($f['tipo']=='pausa' || $f['tipo']=='salida') && $start) { 
            $segundos += (strtotime($f['fecha_hora']) - $start); 
            $start = null; 
        }
    }
    $horasReales = round($segundos / 3600, 1);

} catch (Exception $e) {
    error_log("Error Dashboard: " . $e->getMessage());
}

// Datos Muro Admin
if($esAdmin) {
    $pendientes = $pdo->query("SELECT COUNT(*) FROM ausencias WHERE estado = 'pendiente'")->fetchColumn();
    $estadoPlantilla = $pdo->query("SELECT u.id, u.nombre, u.foto_url, f.tipo as ultimo_estado FROM usuarios u LEFT JOIN (SELECT f1.usuario_id, f1.tipo FROM fichajes f1 WHERE f1.id = (SELECT MAX(f2.id) FROM fichajes f2 WHERE f2.usuario_id = f1.usuario_id AND DATE(f2.fecha_hora) = CURDATE())) f ON u.id = f.usuario_id WHERE u.rol = 'empleado' ORDER BY u.nombre ASC")->fetchAll();
    $usuariosLista = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'empleado' ORDER BY nombre ASC")->fetchAll();
    $ausenciasHoy = $pdo->query("SELECT u.nombre, a.tipo FROM ausencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.estado = 'aprobado' AND u.rol = 'empleado' AND CURDATE() BETWEEN a.fecha_inicio AND a.fecha_fin")->fetchAll();
    $festivoHoy = $pdo->query("SELECT nombre FROM festivos WHERE fecha = CURDATE()")->fetchColumn();
} else {
    $notif = $pdo->prepare("SELECT id, tipo, estado FROM ausencias WHERE usuario_id = ? AND notificacion_vista = 0 AND estado != 'pendiente'");
    $notif->execute([$miId]); $notifList = $notif->fetchAll();
    $stmtMiEstado = $pdo->prepare("SELECT tipo FROM fichajes WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmtMiEstado->execute([$miId]); $miEstadoActual = $stmtMiEstado->fetchColumn() ?: 'fuera';
}

$nextFest = $pdo->query("SELECT nombre, fecha FROM festivos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 1")->fetch();
?>

<div class="max-w-7xl mx-auto pb-20">

    <?php if(!$esAdmin): foreach($notifList as $n): ?>
        <div class="bg-white border-l-8 <?php echo ($n['estado']=='aprobado')?'border-emerald-500':'border-rose-500'; ?> p-6 rounded-3xl mb-6 shadow-xl flex justify-between items-center animate-bounce mx-2">
            <div class="flex items-center gap-4"><i class="fas fa-bell <?php echo ($n['estado']=='aprobado')?'text-emerald-500':'text-rose-500'; ?> text-xl"></i><p class="text-sm font-bold">RRHH: Tu petición de <?php echo strtoupper($n['tipo']); ?> ha sido <span class="underline"><?php echo strtoupper($n['estado']); ?></span>.</p></div>
            <button onclick="marcarLeida(<?php echo $n['id']; ?>)" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase">Entendido</button>
        </div>
    <?php endforeach; endif; ?>

    <div class="bg-slate-900 p-8 md:p-12 rounded-[50px] text-white shadow-2xl mb-10 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden mx-2">
        <div class="relative z-10 text-center md:text-left"><h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter mb-1">Hola, <?php echo explode(' ', $user['nombre'])[0]; ?> 👋</h1></div>
        <?php if($nextFest): ?>
            <div class="relative z-10 bg-white/5 backdrop-blur-xl px-6 py-4 rounded-[30px] border border-white/10 text-center">
                <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest leading-none mb-1">Próximo Festivo</p>
                <p class="font-black text-sm uppercase italic"><?php echo date('d M', strtotime($nextFest['fecha'])); ?> — <?php echo $nextFest['nombre']; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mx-2">
        <div class="lg:col-span-1 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col items-center">
            <?php if($esAdmin): ?>
            <form method="GET" class="w-full mb-8">
                <input type="hidden" name="p" value="dashboard">
                <select name="ver_emp" onchange="this.form.submit()" class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-2xl font-black text-xs uppercase outline-none focus:border-blue-500 transition shadow-inner">
                    <option value="all" <?php echo ($filtroEmp == 'all')?'selected':''; ?>>✨ TODA LA PLANTILLA</option>
                    <?php foreach($usuariosLista as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($filtroEmp == $u['id'])?'selected':''; ?>><?php echo $u['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php else: ?>
                <h3 class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-8 text-center">Horas acumuladas este mes</h3>
            <?php endif; ?>

            <div class="relative w-full aspect-square max-w-[180px]">
                <canvas id="chartProd"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center font-black text-2xl italic text-slate-800">
                    <?php echo ($horasObjetivo > 0) ? round(($horasReales/$horasObjetivo)*100) : 0; ?>%
                </div>
            </div>

            <div class="mt-8 w-full space-y-3">
                <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Objetivo</span>
                    <span class="font-black text-slate-700"><?php echo round($horasObjetivo, 1); ?>h</span>
                </div>
                <div class="flex justify-between items-center bg-emerald-50 p-4 rounded-2xl border border-emerald-100">
                    <span class="text-[9px] font-black text-emerald-600 uppercase">Realizado</span>
                    <span class="font-black text-emerald-700"><?php echo $horasReales; ?>h</span>
                </div>
            </div>
        </div>

        <?php if($esAdmin): ?>
            <div class="lg:col-span-2 space-y-10">
                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex-1">
                        <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-6">Situación Hoy</h2>
                        <?php if($festivoHoy): ?>
                            <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-blue-800 font-bold text-xs mb-2 italic text-center">HOY ES: <?php echo strtoupper($festivoHoy); ?></div>
                        <?php endif; ?>
                        <?php foreach($ausenciasHoy as $a): ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-2">
                                <span class="font-black text-slate-700 text-xs italic"><?php echo $a['nombre']; ?></span>
                                <span class="text-[9px] font-black uppercase text-rose-500"><?php echo $a['tipo']; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($ausenciasHoy) && !$festivoHoy): echo '<p class="text-slate-300 italic text-xs">Sin ausencias registradas hoy</p>'; endif; ?>
                        <div class="mt-6 border-t pt-4"><a href="index.php?p=gestion_ausencias" class="text-xs font-black text-blue-600 uppercase tracking-widest hover:underline italic"><i class="fas fa-arrow-right mr-2"></i> Gestión de Peticiones</a></div>
                    </div>
                    <?php if($pendientes > 0): ?>
                        <a href="index.php?p=gestion_ausencias" class="bg-rose-500 text-white p-10 rounded-[40px] shadow-2xl text-center group transition hover:scale-105 animate-pulse">
                            <p class="text-4xl font-black italic mb-1"><?php echo $pendientes; ?></p>
                            <p class="text-[9px] font-black uppercase tracking-widest">Peticiones Pendientes</p>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 md:p-12">
                    <h2 class="text-sm font-black text-slate-800 uppercase italic tracking-widest mb-10">Monitor de Equipo</h2>
                    <div class="grid grid-cols-3 md:grid-cols-5 gap-8 text-center">
                        <?php foreach($estadoPlantilla as $p): 
                            $color = ($p['ultimo_estado'] == 'entrada' || $p['ultimo_estado'] == 'reanudar') ? 'bg-emerald-500' : (($p['ultimo_estado'] == 'pausa') ? 'bg-amber-400' : 'bg-slate-200'); 
                            $avatar = $p['foto_url'] ?: 'https://ui-avatars.com/api/?background=random&name='.urlencode($p['nombre']); 
                        ?>
                            <div class="flex flex-col items-center group">
                                <div class="relative mb-3">
                                    <img src="<?php echo $avatar; ?>" class="w-16 h-16 rounded-[24px] bg-slate-50 border-2 border-slate-100 object-cover group-hover:scale-110 transition duration-300 shadow-sm">
                                    <div class="absolute -top-1 -right-1 w-4 h-4 <?php echo $color; ?> border-2 border-white rounded-full shadow-md"></div>
                                </div>
                                <p class="text-[10px] font-black text-slate-700 uppercase italic"><?php echo explode(' ', $p['nombre'])[0]; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="lg:col-span-2 bg-white rounded-[50px] shadow-sm border border-slate-200 p-10 text-center flex flex-col justify-center h-full">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <button onclick="fichar('entrada')" <?php echo ($miEstadoActual == 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-emerald-500 hover:bg-emerald-600 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-play mb-4 group-hover:scale-110 transition"></i> ENTRAR</button>
                    <button onclick="fichar('pausa')" <?php echo ($miEstadoActual != 'entrada') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-amber-400 hover:bg-amber-500 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-pause mb-4 group-hover:scale-110 transition"></i> PAUSA</button>
                    <button onclick="fichar('salida')" <?php echo ($miEstadoActual == 'fuera' || $miEstadoActual == 'salida') ? 'disabled' : ''; ?> class="disabled:opacity-10 bg-rose-500 hover:bg-rose-600 text-white py-12 rounded-[45px] font-black text-2xl shadow-xl active:scale-95 transition flex flex-col items-center group"><i class="fas fa-power-off mb-4 group-hover:scale-110 transition"></i> SALIR</button>
                </div>
                
                <div class="flex flex-col gap-4 mt-auto">
                    <div class="bg-blue-600 text-white p-6 rounded-[40px] flex justify-between items-center shadow-xl shadow-blue-200">
                        <p class="text-[10px] font-black uppercase italic tracking-widest">Saldo Vacaciones:</p>
                        <p class="text-3xl font-black italic"><?php echo round($user['dias_vacaciones_disponibles'], 2); ?> d</p>
                    </div>
                    <div>
                        <a href="index.php?p=solicitudes" class="block w-full bg-slate-900 text-white py-5 px-10 rounded-[30px] font-black uppercase text-xs tracking-widest shadow-xl hover:bg-emerald-600 transition">Solicitar Vacaciones / Bajas</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Gráfico de productividad
new Chart(document.getElementById('chartProd'), { 
    type: 'doughnut', 
    data: { 
        datasets: [{ 
            data: [<?php echo $horasReales; ?>, <?php echo max(0, $horasObjetivo - $horasReales); ?>], 
            backgroundColor: ['#10b981', '#f1f5f9'], 
            borderWidth: 0, 
            cutout: '85%', 
            borderRadius: 15 
        }] 
    }, 
    options: { 
        plugins: { legend: { display: false } }, 
        animation: { duration: 2000, easing: 'easeOutQuart' } 
    } 
});

function fichar(tipo) {
    Swal.fire({ title: 'Obteniendo GPS...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    navigator.geolocation.getCurrentPosition(pos => {
        const params = new URLSearchParams();
        params.append('tipo', tipo);
        params.append('lat', pos.coords.latitude);
        params.append('lng', pos.coords.longitude);
        
        fetch('api/fichar.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
            body: params.toString() 
        }).then(res => res.json()).then(data => {
            if(data.success) {
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        });
    }, err => { 
        Swal.fire({ icon: 'error', title: 'GPS Requerido', text: 'Para fichar en Benigànim es necesario activar la ubicación.' }); 
    });
}

function marcarLeida(id) { 
    const params = new URLSearchParams();
    params.append('id', id);
    fetch('api/notificacion_leida.php', { 
        method: 'POST', 
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
        body: params.toString() 
    }).then(() => location.reload()); 
}
</script>
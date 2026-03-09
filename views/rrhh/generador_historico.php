<?php
require_once 'config/config.php';

// Seguridad: Solo administradores
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado. Solo administradores pueden generar históricos.");
}

$reportHtml = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $anio = (int)$_POST['anio'];
    $entrada_m = $_POST['entrada_m'];
    $salida_m = $_POST['salida_m'];
    $entrada_t = $_POST['entrada_t'] ?? '';
    $salida_t = $_POST['salida_t'] ?? '';
    $variacion = (int)$_POST['variacion']; // Minutos aleatorios arriba/abajo
    $vacaciones_inicio = $_POST['vacaciones_inicio'];
    $vacaciones_fin = $_POST['vacaciones_fin'];
    $dias_trabajo = $_POST['dias_trabajo'] ?? [1,2,3,4,5]; // Días de la semana

    // Función para calcular festivos móviles (Semana Santa) y fijos en la CV
    function obtenerFestivos($anio) {
        $festivos = [
            "$anio-01-01", // Año Nuevo
            "$anio-01-06", // Reyes
            "$anio-03-19", // San José (Fallas)
            "$anio-05-01", // Día del Trabajador
            "$anio-06-24", // San Juan
            "$anio-08-15", // Asunción
            "$anio-10-09", // Día Comunitat Valenciana
            "$anio-10-12", // Fiesta Nacional
            "$anio-11-01", // Todos los Santos
            "$anio-12-06", // Constitución
            "$anio-12-08", // Inmaculada
            "$anio-12-25"  // Navidad
        ];
        
        // Calcular Semana Santa (Viernes Santo y Lunes de Pascua)
        $diasPascua = easter_days($anio);
        $fechaPascua = date("Y-m-d", easter_date($anio));
        $viernesSanto = date("Y-m-d", strtotime("$fechaPascua - 2 days"));
        $lunesPascua = date("Y-m-d", strtotime("$fechaPascua + 1 days"));
        
        $festivos[] = $viernesSanto;
        $festivos[] = $lunesPascua;
        
        // Placeholder festivos locales Benigànim (Ej: Fiestas patronales a finales de agosto)
        $festivos[] = "$anio-08-30"; 
        $festivos[] = "$anio-08-31";

        return $festivos;
    }

    $festivosAnio = obtenerFestivos($anio);

    function generarHoraConVariacion($horaBase, $variacion) {
        if (empty($horaBase)) return '';
        $segundosVariacion = rand(-$variacion * 60, $variacion * 60);
        $timestamp = strtotime($horaBase) + $segundosVariacion;
        return date('H:i', $timestamp);
    }

    function esVacaciones($fecha, $inicio, $fin) {
        if (empty($inicio) || empty($fin)) return false;
        $f = strtotime($fecha);
        return ($f >= strtotime($inicio) && $f <= strtotime($fin));
    }

    ob_start();
    
    // Generar 12 meses
    for ($mes = 1; $mes <= 12; $mes++) {
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $mesStr = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $segundosMesTotal = 0;

        ?>
        <div class="hoja-pdf">
            <div class="header-informe">
                <h1 style="text-align: center; font-weight: bold; font-size: 1.5rem; text-decoration: underline;">REGISTRO DE JORNADA</h1>
                <h2 style="text-align: center; font-weight: normal; font-size: 1rem; margin-bottom: 20px;">BENIGÀNIM - CUMPLIMIENTO RD-LEY 8/2019</h2>
                
                <table class="tabla-info">
                    <tr>
                        <td style="font-weight: bold;">EMPRESA</td>
                        <td><?php echo EMPRESA_NOMBRE; ?> (<?php echo EMPRESA_CIF; ?>)</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">TRABAJADOR/A</td>
                        <td style="text-transform: uppercase;"><?php echo $nombre; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">MES/AÑO</td>
                        <td><?php echo "$mesStr/$anio"; ?></td>
                    </tr>
                </table>
            </div>

            <table class="tabla-registros">
                <thead>
                    <tr>
                        <th>DIA</th>
                        <th>ENTRADA</th>
                        <th>SALIDA</th>
                        <th>UBICACIÓN (GPS)</th>
                        <th>TOTAL HORAS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($dia = 1; $dia <= 31; $dia++) {
                        if ($dia > $diasEnMes) {
                            // Filas vacías para rellenar tabla hasta el 31 si el mes tiene menos días
                            echo "<tr><td>$dia</td><td></td><td></td><td></td><td></td></tr>";
                            continue;
                        }

                        $fechaActual = "$anio-$mesStr-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                        $diaSemana = date('N', strtotime($fechaActual));
                        
                        $esFestivo = in_array($fechaActual, $festivosAnio);
                        $esFinde = !in_array($diaSemana, $dias_trabajo);
                        $estaDeVacaciones = esVacaciones($fechaActual, $vacaciones_inicio, $vacaciones_fin);

                        if ($esFinde || $esFestivo || $estaDeVacaciones) {
                            echo "<tr><td>$dia</td><td></td><td></td><td></td><td>00:00</td></tr>";
                        } else {
                            // Día laborable - Generamos horas
                            $e1 = generarHoraConVariacion($entrada_m, $variacion);
                            $s1 = generarHoraConVariacion($salida_m, $variacion);
                            $e2 = generarHoraConVariacion($entrada_t, $variacion);
                            $s2 = generarHoraConVariacion($salida_t, $variacion);

                            $htmlEntradas = $e1;
                            $htmlSalidas = $s1;
                            $segundosDia = (strtotime($s1) - strtotime($e1));

                            if (!empty($e2) && !empty($s2)) {
                                $htmlEntradas .= "<br>$e2";
                                $htmlSalidas .= "<br>$s2";
                                $segundosDia += (strtotime($s2) - strtotime($e2));
                            }

                            $segundosMesTotal += $segundosDia;
                            $hDia = floor($segundosDia / 3600);
                            $mDia = floor(($segundosDia / 60) % 60);
                            $totalDiaStr = str_pad($hDia, 2, '0', STR_PAD_LEFT) . ":" . str_pad($mDia, 2, '0', STR_PAD_LEFT);

                            echo "<tr>
                                    <td>$dia</td>
                                    <td>$htmlEntradas</td>
                                    <td>$htmlSalidas</td>
                                    <td>REGISTRADO</td>
                                    <td>$totalDiaStr</td>
                                  </tr>";
                        }
                    }
                    
                    $hMes = floor($segundosMesTotal / 3600);
                    $mMes = floor(($segundosMesTotal / 60) % 60);
                    $totalMesStr = "{$hMes}h {$mMes}m";
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="border: none;"></td>
                        <td style="font-weight: bold; background-color: #f1f5f9;">CÓMPUTO TOTAL MENSUAL</td>
                        <td style="font-weight: bold; background-color: #f1f5f9;"><?php echo $totalMesStr; ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="firmas-container">
                <div class="caja-firma">
                    <p>FIRMA EMPRESA</p>
                </div>
                <div class="caja-firma">
                    <p>FIRMA TRABAJADOR/A</p>
                </div>
            </div>

            <div class="footer-legal">
                © <?php echo $anio; ?> CVTOOLS HR MANAGER • REGISTRO HORARIO DIGITAL • INNOVAI.ES
            </div>
        </div>
        <?php
    }
    $reportHtml = ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador Histórico - CV Tools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos para la generación del PDF */
        body { background-color: #f8fafc; }
        .hoja-pdf {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            position: relative;
        }
        .tabla-info { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .tabla-info td { padding: 5px; border: 1px solid #000; }
        
        .tabla-registros { width: 100%; border-collapse: collapse; text-align: center; font-size: 0.9rem; }
        .tabla-registros th, .tabla-registros td { border: 1px solid #000; padding: 4px; }
        .tabla-registros th { background-color: #f1f5f9; font-weight: bold; }
        
        .firmas-container { display: flex; justify-content: space-around; margin-top: 50px; }
        .caja-firma { text-align: center; font-weight: bold; border-top: 1px solid #000; width: 40%; padding-top: 10px; margin-top: 50px; }
        
        .footer-legal { text-align: center; font-size: 0.7rem; color: #64748b; margin-top: 30px; }

        @media print {
            body { background-color: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .hoja-pdf { 
                margin: 0; 
                padding: 15mm; 
                box-shadow: none; 
                width: 100%; 
                height: 100vh;
                page-break-after: always; 
            }
            .hoja-pdf:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>

<div class="max-w-4xl mx-auto mt-10 p-8 bg-white rounded-[30px] shadow-xl border border-slate-200 no-print mb-10">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase italic">Generador Histórico</h1>
            <p class="text-slate-400 font-bold text-sm">Creación de informes retroactivos simulados</p>
        </div>
        <a href="../index.php?p=dashboard" class="text-blue-500 font-bold hover:underline"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <form method="POST" action="" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-black uppercase text-slate-500 mb-2">Nombre del Trabajador</label>
                <input type="text" name="nombre" required class="w-full bg-slate-50 border p-3 rounded-xl" placeholder="Ej: Pablo Vidal">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-500 mb-2">Año a generar</label>
                <select name="anio" required class="w-full bg-slate-50 border p-3 rounded-xl">
                    <option value="2022">2022</option>
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-500 mb-2">Variación Realista</label>
                <select name="variacion" class="w-full bg-slate-50 border p-3 rounded-xl">
                    <option value="3">± 3 Minutos</option>
                    <option value="5" selected>± 5 Minutos</option>
                    <option value="10">± 10 Minutos</option>
                </select>
            </div>
        </div>

        <div class="bg-slate-50 p-6 rounded-2xl border">
            <h3 class="font-black text-slate-700 uppercase mb-4 text-sm">Horario Habitual</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Entrada Mañana</label>
                    <input type="time" name="entrada_m" required class="w-full border p-2 rounded-lg" value="08:00">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Salida Mañana</label>
                    <input type="time" name="salida_m" required class="w-full border p-2 rounded-lg" value="13:30">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Entrada Tarde (Opcional)</label>
                    <input type="time" name="entrada_t" class="w-full border p-2 rounded-lg" value="15:00">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Salida Tarde (Opcional)</label>
                    <input type="time" name="salida_t" class="w-full border p-2 rounded-lg" value="17:30">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-slate-50 p-6 rounded-2xl border">
                <h3 class="font-black text-slate-700 uppercase mb-4 text-sm">Días de Trabajo</h3>
                <div class="flex gap-4">
                    <label><input type="checkbox" name="dias_trabajo[]" value="1" checked> L</label>
                    <label><input type="checkbox" name="dias_trabajo[]" value="2" checked> M</label>
                    <label><input type="checkbox" name="dias_trabajo[]" value="3" checked> X</label>
                    <label><input type="checkbox" name="dias_trabajo[]" value="4" checked> J</label>
                    <label><input type="checkbox" name="dias_trabajo[]" value="5" checked> V</label>
                </div>
            </div>
            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                <h3 class="font-black text-blue-800 uppercase mb-4 text-sm">Bloque de Vacaciones</h3>
                <div class="flex gap-4">
                    <div class="w-full">
                        <label class="block text-[10px] font-bold text-blue-600">Desde</label>
                        <input type="date" name="vacaciones_inicio" class="w-full border p-2 rounded-lg text-sm">
                    </div>
                    <div class="w-full">
                        <label class="block text-[10px] font-bold text-blue-600">Hasta</label>
                        <input type="date" name="vacaciones_fin" class="w-full border p-2 rounded-lg text-sm">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-slate-900 text-white font-black uppercase tracking-widest p-4 rounded-xl shadow-xl hover:bg-slate-800 transition">
            <i class="fas fa-cogs mr-2"></i> Generar Año Completo
        </button>
    </form>
</div>

<?php if (!empty($reportHtml)): ?>
    <div class="text-center mb-6 no-print">
        <button onclick="window.print()" class="bg-emerald-500 hover:bg-emerald-600 text-white font-black uppercase px-8 py-4 rounded-[30px] shadow-2xl transition transform hover:scale-105">
            <i class="fas fa-print mr-2"></i> Guardar como PDF
        </button>
        <p class="text-xs text-slate-500 mt-3 font-bold">Asegúrate de seleccionar "Guardar como PDF" en las opciones de destino de la impresora.</p>
    </div>

    <?php echo $reportHtml; ?>
<?php endif; ?>

</body>
</html>
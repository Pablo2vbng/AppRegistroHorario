SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Base de datos: `u249173200_registroCvt`

-- 1. Tabla usuarios (Principal, no tiene dependencias)
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('admin','rrhh','empleado') DEFAULT 'empleado',
  `dias_vacaciones_totales` int(11) DEFAULT 22,
  `dias_vacaciones_disponibles` int(11) DEFAULT 22,
  `fecha_alta` date DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `horario` enum('Mañana','Tarde','Partido','Flexible') DEFAULT 'Flexible',
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `horas_jornada` decimal(4,2) DEFAULT 8.00,
  `dias_laborables` varchar(20) DEFAULT '1,2,3,4,5',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla avisos_generales (Principal)
CREATE TABLE `avisos_generales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensaje` text NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla festivos (Principal)
CREATE TABLE `festivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `tipo` enum('nacional','comunidad','local') DEFAULT 'nacional',
  `laborable_canjeado` tinyint(1) DEFAULT 0,
  `descuenta_vacaciones` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla ausencias (Depende de usuarios)
CREATE TABLE `ausencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('vacaciones','medico','personal','maternidad','otro','permuta') DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `motivo` text DEFAULT NULL,
  `archivo_justificante` varchar(255) DEFAULT NULL,
  `fecha_permuta_trabajo` date DEFAULT NULL,
  `recuperable` tinyint(1) DEFAULT 0,
  `es_por_horas` tinyint(1) DEFAULT 0,
  `horas_solicitadas` decimal(5,2) DEFAULT NULL,
  `notificacion_vista` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `ausencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla avisos_leidos (Depende de avisos_generales y usuarios)
CREATE TABLE `avisos_leidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aviso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_lectura` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `avisos_leidos_ibfk_1` FOREIGN KEY (`aviso_id`) REFERENCES `avisos_generales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avisos_leidos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla documentos (Depende de usuarios)
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `tipo_doc` enum('dni','contrato','nomina','otros') DEFAULT 'otros',
  `ruta` varchar(255) DEFAULT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla fichajes (Depende de usuarios)
CREATE TABLE `fichajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','pausa','reanudar','salida') NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `ip_registro` varchar(45) DEFAULT NULL,
  `geolocalizacion` varchar(255) DEFAULT NULL,
  `editado_por_admin` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `fuera_rango` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fichajes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

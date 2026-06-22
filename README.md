# HR Manager - Registro Horario Digital

[![PHP](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com/)

Plataforma integral para la gestión de recursos humanos, control de jornada, auditoría GPS y automatización de ausencias. Desarrollada específicamente para cumplir con los requisitos del RD-Ley 8/2019 sobre el registro de jornada laboral efectivo.

## Características Principales

* Fichaje Geolocalizado: Registro de entradas, pausas y salidas con control de coordenadas (latitud/longitud) para auditar si el empleado está dentro del rango de la oficina.
* Gestión de Ausencias Inteligente: Solicitud de vacaciones y bajas médicas con cálculo automático de días laborables, descontando fines de semana y festivos.
* Calendario de Festivos: Gestión de fiestas nacionales, autonómicas y locales para cuadre automático.
* Automatización (Cron Jobs): 
  * Integración con WhatsApp API para enviar recordatorios automáticos de fichaje.
  * Simulador de autofichaje con variaciones aleatorias deterministas para perfiles o directivos específicos.
* Informes PDF: Generador de históricos retroactivos con simulación de variaciones temporales realistas para firma.

## Requisitos del Sistema

* Servidor Web (Apache/Nginx - testeado en Hostinger).
* PHP 8.0 o superior.
* MySQL 5.7 / 8.0 o MariaDB.
* Extensión PDO habilitada en PHP.
* Extensión cURL habilitada.

## Instalación y Puesta en Marcha

1. Subida de Archivos:
   Clona o sube los archivos de este repositorio al directorio raíz de tu servidor web.

2. Base de Datos:
   * Crea una base de datos MySQL vacía en tu servidor.
   * Importa el archivo `database.sql` incluido en la raíz de este repositorio. Este archivo contiene la estructura limpia de las tablas, sin datos de prueba, lista para funcionar.

3. Configuración de Variables:
   * Navega a la carpeta `/config/`.
   * Edita el archivo `config.php` y rellena los datos de la empresa, sede y credenciales de base de datos.

4. Permisos de Carpetas:
   * Asegúrate de que la carpeta `/uploads/justificantes/` tenga permisos de escritura (generalmente 0755) para permitir a los empleados la subida de archivos médicos y justificantes.

5. Automatizaciones (Cron Jobs):
   * En tu panel de hosting, crea Tareas Cron configuradas para ejecutarse cada minuto (* * * * *).
   * Utiliza el comando cURL apuntando a la URL absoluta del archivo para asegurar la ejecución, ejemplo: 
     `curl -s "https://tu-dominio.com/cron/tu_archivo.php?token=TU_TOKEN" > /dev/null`

## Archivo de Configuración (config.php)

Este es el archivo base necesario para conectar el sistema a la base de datos y definir las variables globales. Cópialo en `config/config.php` y rellena los espacios vacíos con los datos de tu entorno:

```php
<?php
// 1. Zona Horaria
date_default_timezone_set('Europe/Madrid');

// 2. PARÁMETROS DE EMPRESA
define('EMPRESA_NOMBRE', '');
define('EMPRESA_CIF', '');
define('EMPRESA_SEDE', '');
define('EMPRESA_DIRECCION', '');
define('EMPRESA_LOGO', '');

// 3. COORDENADAS OFICINA  
define('OFICINA_LAT', );   
define('OFICINA_LNG', );   
define('RADIO_PERMITIDO', );    

// 4. RRHH
define('GESTORA_NOMBRE', '');
define('GESTORA_EMAIL', '');
define('GESTORA_WHATSAPP', '');

// 5. DB
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', ''); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $ahora = new DateTime();
    $offset = $ahora->format('P');
    $pdo->exec("SET time_zone='$offset';");
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

## Arquitectura de la Base de Datos

El sistema se estructura principalmente sobre 4 tablas relacionales:

usuarios: Contiene la plantilla. Almacena el rol (empleado/admin), días laborables por contrato y saldo vivo de vacaciones.

fichajes: Tabla transaccional que registra cada movimiento (entrada, pausa, reanudar, salida), IP y coordenadas (lat/lng), indicando mediante un flag (fuera_rango) si se fichó lejos del radio de la oficina.

ausencias: Bandeja de solicitudes de permisos. Guarda las rutas de los justificantes y un flag que dicta si el permiso aprobado descuenta o no saldo de vacaciones.

festivos: Bloqueos de calendario general. Registra fiestas locales, autonómicas o nacionales, impidiendo el descuento de vacaciones si una solicitud de ausencia abarca estas fechas.

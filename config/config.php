<?php
/**
 * Configuración Principal del Sistema
 * Salón de Belleza - Honduras
 */

// Configuración de Zona Horaria (Honduras)
date_default_timezone_set('America/Tegucigalpa');

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'salon_belleza');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la Aplicación
define('APP_NAME', 'Sistema de Gestión - Salón de Belleza');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/salon_belleza/');

// Configuración de Sesiones
define('SESSION_TIMEOUT', 1800); // 30 minutos en segundos
define('SESSION_NAME', 'SALON_SESSION');

// Configuración de Seguridad
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_TIME', 900); // 15 minutos de bloqueo

// Configuración de Archivos
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_PRODUCTOS', UPLOAD_DIR . 'productos/');
define('UPLOAD_FACTURAS', UPLOAD_DIR . 'facturas/');
define('MAX_FILE_SIZE', 2097152); // 2MB máximo para imágenes

// Configuración de Impuestos (Honduras)
define('ISV_SERVICIOS', 15); // 15% para servicios
define('ISV_PRODUCTOS', 18); // 18% para productos

// Configuración de Paginación
define('DEFAULT_PAGE_SIZE', 20);

// Niveles de Error (Desarrollo vs Producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

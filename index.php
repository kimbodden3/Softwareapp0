<?php
/**
 * Punto de Entrada Principal (Router)
 * Maneja todas las solicitudes a la aplicación
 */

// Incluir configuración y clases base
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

// Inicializar autenticación
$auth = new Auth();

// Obtener página solicitada
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['accion'] ?? 'listar';

// Verificar autenticación (excepto para login)
if ($page !== 'login' && !$auth->isAuthenticated()) {
    redirect(APP_URL . 'modules/login/index.php');
}

// Si está autenticado y va a login, redirigir al dashboard
if ($page === 'login' && $auth->isAuthenticated()) {
    redirect(APP_URL . 'index.php?page=dashboard');
}

// Usuario actual
$currentUser = $auth->getCurrentUser();

// Manejar routing básico
switch ($page) {
    case 'login':
        include __DIR__ . '/modules/login/index.php';
        break;
        
    case 'dashboard':
        // Dashboard - Vista principal
        $currentPage = 'dashboard';
        $pageTitle = 'Panel de Control';
        
        // Obtener estadísticas para el dashboard
        $db = Database::getInstance();
        
        // Ventas del día
        $ventasHoy = $db->fetchOne(
            "SELECT COUNT(*) as total_facturas, SUM(total) as total_vendido 
             FROM facturas 
             WHERE DATE(fecha_emision) = CURDATE() AND estado = 'pagada'"
        );
        
        // Citas del día
        $citasHoy = $db->fetchOne(
            "SELECT COUNT(*) as total_citas 
             FROM citas 
             WHERE DATE(fecha_hora) = CURDATE()"
        );
        
        // Productos con stock bajo
        $stockBajo = getLowStockAlerts();
        
        // Últimas facturas
        $ultimasFacturas = $db->fetchAll(
            "SELECT f.*, c.nombre as cliente_nombre 
             FROM facturas f 
             LEFT JOIN clientes c ON f.cliente_id = c.id 
             WHERE f.estado = 'pagada' 
             ORDER BY f.fecha_emision DESC 
             LIMIT 5"
        );
        
        // Top servicios del mes
        $topServicios = $db->fetchAll(
            "SELECT s.nombre, COUNT(df.id) as veces_vendido, SUM(df.total_linea) as total_ingresos
             FROM detalle_factura df
             INNER JOIN servicios s ON df.item_id = s.id AND df.tipo_item = 'servicio'
             INNER JOIN facturas f ON df.factura_id = f.id
             WHERE f.estado = 'pagada' AND MONTH(f.fecha_emision) = MONTH(CURDATE())
             GROUP BY s.id
             ORDER BY veces_vendido DESC
             LIMIT 5"
        );
        
        ob_start();
        include __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'pos':
        // Punto de Venta
        if (!$auth->hasRole(['admin', 'cajero'])) {
            die('Acceso denegado');
        }
        
        $currentPage = 'pos';
        $pageTitle = 'Punto de Venta';
        
        ob_start();
        include __DIR__ . '/modules/pos/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'facturacion':
        // Facturación
        if (!$auth->hasRole('admin')) {
            die('Acceso denegado');
        }
        
        $currentPage = 'facturacion';
        $pageTitle = 'Facturación';
        
        ob_start();
        include __DIR__ . '/modules/facturacion/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'inventario':
        // Inventario
        if (!$auth->hasRole('admin')) {
            die('Acceso denegado');
        }
        
        $currentPage = 'inventario';
        $pageTitle = 'Inventario';
        
        ob_start();
        include __DIR__ . '/modules/inventario/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'proveedores':
        // Proveedores
        if (!$auth->hasRole('admin')) {
            die('Acceso denegado');
        }
        
        $currentPage = 'proveedores';
        $pageTitle = 'Proveedores';
        
        ob_start();
        include __DIR__ . '/modules/proveedores/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'clientes':
        // Clientes
        $currentPage = 'clientes';
        $pageTitle = 'Clientes';
        
        ob_start();
        include __DIR__ . '/modules/clientes/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'cuentas_por_cobrar':
        // Cuentas por Cobrar
        if (!$auth->hasRole(['admin', 'cajero'])) {
            die('Acceso denegado');
        }
        
        $currentPage = 'cuentas_por_cobrar';
        $pageTitle = 'Cuentas por Cobrar';
        
        ob_start();
        include __DIR__ . '/modules/cuentas_por_cobrar/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'servicios':
        // Servicios
        $currentPage = 'servicios';
        $pageTitle = 'Servicios';
        
        ob_start();
        include __DIR__ . '/modules/servicios/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'citas':
        // Citas
        $currentPage = 'citas';
        $pageTitle = 'Gestión de Citas';
        
        ob_start();
        include __DIR__ . '/modules/citas/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    case 'reportes':
        // Reportes
        if (!$auth->hasRole('admin')) {
            die('Acceso denegado');
        }
        
        $currentPage = 'reportes';
        $pageTitle = 'Reportes';
        
        ob_start();
        include __DIR__ . '/modules/reportes/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/includes/template.php';
        break;
        
    default:
        // Página no encontrada
        http_response_code(404);
        die('Página no encontrada');
}

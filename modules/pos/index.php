<?php
/**
 * Módulo Punto de Venta (POS)
 * Maneja las ventas y generación de facturas
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$db = Database::getInstance();

// Verificar permisos
if (!$auth->hasRole(['admin', 'cajero'])) {
    die('Acceso denegado');
}

$accion = $_GET['accion'] ?? 'index';
$error = '';
$success = '';

switch ($accion) {
    case 'procesar_venta':
        // Procesar venta AJAX
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'No hay items en la venta']);
            exit;
        }
        
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Obtener configuración CAI
            $caiConfig = getCurrentCAIConfig();
            if (!$caiConfig) {
                throw new Exception('No hay configuración CAI válida. Contacte al administrador.');
            }
            
            // Generar número de factura
            $consecutivo = getNextFacturaConsecutive();
            $numeroFactura = generateFacturaNumber(
                $caiConfig['establecimiento'],
                $caiConfig['punto_emision'],
                $consecutivo
            );
            
            // Calcular totales
            $subTotal = 0;
            $isv15 = 0;
            $isv18 = 0;
            $descuento = $data['descuento'] ?? 0;
            
            foreach ($data['items'] as $item) {
                $subTotal += $item['precio'] * $item['cantidad'];
                
                if ($item['tipo'] === 'servicio') {
                    $isv15 += calculateISVServicios($item['precio'] * $item['cantidad']);
                } else {
                    $isv18 += calculateISVProductos($item['precio'] * $item['cantidad']);
                }
            }
            
            $total = $subTotal + $isv15 + $isv18 - $descuento;
            
            // Insertar factura
            $sqlFactura = "INSERT INTO facturas 
                          (numero_factura, cai, cliente_id, usuario_id, fecha_emision, 
                           sub_total, descuento, isv_15, isv_18, total, metodo_pago) 
                          VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->query(
                $sqlFactura,
                'ssiiiddiis',
                [
                    $numeroFactura,
                    $caiConfig['cai'],
                    $data['cliente_id'] ?? null,
                    $auth->getCurrentUserId(),
                    $subTotal,
                    $descuento,
                    $isv15,
                    $isv18,
                    $total,
                    $data['metodo_pago'] ?? 'efectivo'
                ]
            );
            
            if (!$stmt) {
                throw new Exception('Error al crear la factura');
            }
            
            $facturaId = $db->lastInsertId();
            
            // Insertar detalles y actualizar stock
            foreach ($data['items'] as $item) {
                $isvAplicado = ($item['tipo'] === 'servicio') ? ISV_SERVICIOS : ISV_PRODUCTOS;
                $totalLinea = $item['precio'] * $item['cantidad'];
                
                $sqlDetalle = "INSERT INTO detalle_factura 
                              (factura_id, tipo_item, item_id, cantidad, precio_unitario, isv_aplicado, total_linea) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmtDetalle = $db->query(
                    $sqlDetalle,
                    'isiidid',
                    [
                        $facturaId,
                        $item['tipo'],
                        $item['id'],
                        $item['cantidad'],
                        $item['precio'],
                        $isvAplicado,
                        $totalLinea
                    ]
                );
                
                if (!$stmtDetalle) {
                    throw new Exception('Error al guardar detalle de factura');
                }
                
                // Actualizar stock si es producto
                if ($item['tipo'] === 'producto') {
                    $sqlStock = "UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?";
                    $stmtStock = $db->query($sqlStock, 'ii', [$item['cantidad'], $item['id']]);
                    
                    if (!$stmtStock) {
                        throw new Exception('Error al actualizar stock');
                    }
                    
                    // Registrar movimiento de inventario
                    registerInventoryMovement(
                        $item['id'],
                        'salida',
                        $item['cantidad'],
                        'Venta - Factura ' . $numeroFactura,
                        $auth->getCurrentUserId()
                    );
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'factura_id' => $facturaId,
                'numero_factura' => $numeroFactura,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            error_log($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'obtener_productos':
        // Obtener productos para búsqueda AJAX
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        $sql = "SELECT p.id, p.nombre, p.precio_venta as precio, p.stock_actual, 
                       'producto' as tipo, p.codigo_barras
                FROM productos p
                WHERE p.estado = 1 AND p.stock_actual > 0
                AND (p.nombre LIKE ? OR p.codigo_barras LIKE ?)
                LIMIT 20";
        
        $productos = $db->fetchAll($sql, 'ss', ['%' . $term . '%', '%' . $term . '%']);
        
        echo json_encode($productos);
        break;
        
    case 'obtener_servicios':
        // Obtener servicios para búsqueda AJAX
        header('Content-Type: application/json');
        
        $categoria = $_GET['categoria'] ?? '';
        
        if ($categoria) {
            $sql = "SELECT s.id, s.nombre, s.precio, s.duracion_minutos,
                           'servicio' as tipo, c.nombre as categoria
                    FROM servicios s
                    INNER JOIN categorias_servicios c ON s.categoria_id = c.id
                    WHERE s.estado = 1 AND c.id = ?
                    ORDER BY s.nombre";
            
            $servicios = $db->fetchAll($sql, 'i', [$categoria]);
        } else {
            $sql = "SELECT s.id, s.nombre, s.precio, s.duracion_minutos,
                           'servicio' as tipo, c.nombre as categoria
                    FROM servicios s
                    INNER JOIN categorias_servicios c ON s.categoria_id = c.id
                    WHERE s.estado = 1
                    ORDER BY c.nombre, s.nombre";
            
            $servicios = $db->fetchAll($sql);
        }
        
        echo json_encode($servicios);
        break;
        
    case 'obtener_categorias':
        // Obtener categorías de servicios AJAX
        header('Content-Type: application/json');
        
        $sql = "SELECT id, nombre FROM categorias_servicios WHERE estado = 1 ORDER BY nombre";
        $categorias = $db->fetchAll($sql);
        
        echo json_encode($categorias);
        break;
        
    default:
        // Vista principal del POS
        include __DIR__ . '/pos_view.php';
        break;
}

<?php
/**
 * Módulo: Cuentas por Cobrar - Procesamiento de Pagos y Creación
 * Sistema de Gestión para Salón de Belleza
 */

session_start();
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::verificarSesion();
Auth::verificarRol(['admin', 'cajero']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conexion = $db->getConexion();
$usuario_id = $_SESSION['usuario_id'];

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            crearCuentaPorCobrar($conexion, $usuario_id);
            break;
        case 'pagar':
            registrarPago($conexion, $usuario_id);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit;
}

/**
 * Función para crear una nueva cuenta por cobrar
 */
function crearCuentaPorCobrar($conexion, $usuario_id) {
    $factura_id = (int)$_POST['factura_id'];
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $notas = trim($_POST['notas'] ?? '');
    
    // Validaciones
    if (empty($factura_id)) {
        throw new Exception('Debe seleccionar una factura');
    }
    
    if (empty($fecha_emision) || empty($fecha_vencimiento)) {
        throw new Exception('Las fechas de emisión y vencimiento son obligatorias');
    }
    
    if ($fecha_vencimiento < $fecha_emision) {
        throw new Exception('La fecha de vencimiento debe ser posterior a la fecha de emisión');
    }
    
    // Obtener datos de la factura
    $stmt = $conexion->prepare("SELECT id, cliente_id, total, estado FROM facturas WHERE id = ?");
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    
    if (!$factura) {
        throw new Exception('Factura no encontrada');
    }
    
    if ($factura['estado'] !== 'pagada') {
        throw new Exception('La factura debe estar pagada para crear una cuenta por cobrar');
    }
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // Crear cuenta por cobrar
        $monto_total = $factura['total'];
        $cliente_id = $factura['cliente_id'];
        
        $stmt_insert = $conexion->prepare("
            INSERT INTO cuentas_por_cobrar 
            (factura_id, cliente_id, usuario_id, monto_total, monto_pagado, saldo_pendiente, 
             fecha_emision, fecha_vencimiento, estado, notas) 
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, 'pendiente', ?)
        ");
        $stmt_insert->bind_param("iiidssss", 
            $factura_id, 
            $cliente_id, 
            $usuario_id, 
            $monto_total, 
            $monto_total, 
            $fecha_emision, 
            $fecha_vencimiento, 
            $notas
        );
        $stmt_insert->execute();
        
        $cuenta_id = $conexion->insert_id;
        
        // Actualizar factura con referencia a cuenta por cobrar
        $stmt_update = $conexion->prepare("
            UPDATE facturas 
            SET es_credito = 1, cuenta_por_cobrar_id = ? 
            WHERE id = ?
        ");
        $stmt_update->bind_param("ii", $cuenta_id, $factura_id);
        $stmt_update->execute();
        
        // Confirmar transacción
        $conexion->commit();
        
        $_SESSION['success'] = 'Cuenta por cobrar creada exitosamente. Factura #' . obtenerNumeroFactura($conexion, $factura_id);
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }
}

/**
 * Función para registrar un pago a cuenta por cobrar
 */
function registrarPago($conexion, $usuario_id) {
    $cuenta_id = (int)$_POST['cuenta_id'];
    $monto_pago = (float)$_POST['monto_pago'];
    $metodo_pago = $_POST['metodo_pago'];
    $referencia_pago = trim($_POST['referencia_pago'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    // Validaciones
    if (empty($cuenta_id)) {
        throw new Exception('ID de cuenta inválido');
    }
    
    if ($monto_pago <= 0) {
        throw new Exception('El monto del pago debe ser mayor a cero');
    }
    
    if (empty($metodo_pago)) {
        throw new Exception('Debe seleccionar un método de pago');
    }
    
    // Obtener datos de la cuenta
    $stmt = $conexion->prepare("
        SELECT cpc.saldo_pendiente, cpc.estado, cpc.monto_total, cpc.monto_pagado, f.numero_factura
        FROM cuentas_por_cobrar cpc
        INNER JOIN facturas f ON cpc.factura_id = f.id
        WHERE cpc.id = ?
    ");
    $stmt->bind_param("i", $cuenta_id);
    $stmt->execute();
    $cuenta = $stmt->get_result()->fetch_assoc();
    
    if (!$cuenta) {
        throw new Exception('Cuenta por cobrar no encontrada');
    }
    
    if ($cuenta['estado'] === 'pagada') {
        throw new Exception('Esta cuenta ya está completamente pagada');
    }
    
    if ($monto_pago > $cuenta['saldo_pendiente']) {
        throw new Exception('El monto del pago no puede exceder el saldo pendiente de L. ' . number_format($cuenta['saldo_pendiente'], 2));
    }
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // Registrar el pago
        $fecha_pago = date('Y-m-d H:i:s');
        
        $stmt_pago = $conexion->prepare("
            INSERT INTO cuentas_por_cobrar_pagos 
            (cuenta_por_cobrar_id, usuario_id, monto_pago, metodo_pago, referencia_pago, notas, fecha_pago) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_pago->bind_param("iiddsss", 
            $cuenta_id, 
            $usuario_id, 
            $monto_pago, 
            $metodo_pago, 
            $referencia_pago, 
            $notas, 
            $fecha_pago
        );
        $stmt_pago->execute();
        
        // Actualizar saldos de la cuenta
        $nuevo_monto_pagado = $cuenta['monto_pagado'] + $monto_pago;
        $nuevo_saldo_pendiente = $cuenta['saldo_pendiente'] - $monto_pago;
        
        // Determinar nuevo estado
        $nuevo_estado = 'pendiente';
        if ($nuevo_saldo_pendiente <= 0.01) { // Margen pequeño por decimales
            $nuevo_estado = 'pagada';
        } elseif ($nuevo_monto_pagado > 0) {
            $nuevo_estado = 'parcial';
        }
        
        $stmt_update = $conexion->prepare("
            UPDATE cuentas_por_cobrar 
            SET monto_pagado = ?, saldo_pendiente = ?, estado = ? 
            WHERE id = ?
        ");
        $stmt_update->bind_param("ddsi", 
            $nuevo_monto_pagado, 
            $nuevo_saldo_pendiente, 
            $nuevo_estado, 
            $cuenta_id
        );
        $stmt_update->execute();
        
        // Confirmar transacción
        $conexion->commit();
        
        $mensaje = 'Pago registrado exitosamente. ';
        if ($nuevo_estado === 'pagada') {
            $mensaje .= '¡Cuenta completamente pagada!';
        } else {
            $mensaje .= 'Saldo restante: L. ' . number_format($nuevo_saldo_pendiente, 2);
        }
        
        $_SESSION['success'] = $mensaje;
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }
}

/**
 * Helper para obtener número de factura
 */
function obtenerNumeroFactura($conexion, $factura_id) {
    $stmt = $conexion->prepare("SELECT numero_factura FROM facturas WHERE id = ?");
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['numero_factura'] ?? '';
}
?>

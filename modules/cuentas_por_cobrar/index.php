<?php
/**
 * Módulo: Cuentas por Cobrar - Listado Principal
 * Sistema de Gestión para Salón de Belleza
 */

session_start();
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::verificarSesion();
Auth::verificarRol(['admin', 'cajero']);

$db = Database::getInstance();
$conexion = $db->getConexion();

// Obtener parámetros de filtro
$estado_filtro = $_GET['estado'] ?? '';
$cliente_filtro = $_GET['cliente'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$sql = "SELECT * FROM v_cuentas_por_cobrar_resumen WHERE 1=1";
$params = [];

if ($estado_filtro) {
    $sql .= " AND estado = ?";
    $params[] = $estado_filtro;
}

if ($cliente_filtro) {
    $sql .= " AND cliente_nombre LIKE ?";
    $params[] = "%{$cliente_filtro}%";
}

if ($fecha_desde) {
    $sql .= " AND fecha_emision >= ?";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $sql .= " AND fecha_emision <= ?";
    $params[] = $fecha_hasta;
}

$sql .= " ORDER BY fecha_vencimiento ASC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener resumen de totales
$sql_resumen = "SELECT 
    COUNT(*) as total_cuentas,
    SUM(saldo_pendiente) as total_pendiente,
    SUM(CASE WHEN estado = 'vencida' THEN saldo_pendiente ELSE 0 END) as total_vencido,
    SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END) as total_por_cobrar
FROM cuentas_por_cobrar 
WHERE estado NOT IN ('cancelada', 'pagada')";
$resumen = $conexion->query($sql_resumen)->fetch_assoc();

include '../../includes/template.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-wallet2"></i> Cuentas por Cobrar</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCuenta">
                    <i class="bi bi-plus-circle"></i> Nueva Cuenta por Cobrar
                </button>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Cuentas Activas</h6>
                    <h3><?= number_format($resumen['total_cuentas'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6>Por Cobrar</h6>
                    <h3>L. <?= number_format($resumen['total_por_cobrar'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Vencido</h6>
                    <h3>L. <?= number_format($resumen['total_vencido'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Pendiente</h6>
                    <h3>L. <?= number_format($resumen['total_pendiente'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filtros de Búsqueda
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= $estado_filtro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="parcial" <?= $estado_filtro === 'parcial' ? 'selected' : '' ?>>Parcial</option>
                        <option value="pagada" <?= $estado_filtro === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                        <option value="vencida" <?= $estado_filtro === 'vencida' ? 'selected' : '' ?>>Vencida</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control" placeholder="Nombre del cliente" value="<?= htmlspecialchars($cliente_filtro) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-info w-100">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Cuentas por Cobrar -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> Listado de Cuentas por Cobrar
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>RTN</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th>Alerta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while ($cuenta = $resultado->fetch_assoc()): ?>
                                <?php
                                $clase_estado = '';
                                switch($cuenta['estado']) {
                                    case 'pagada': $clase_estado = 'bg-success'; break;
                                    case 'parcial': $clase_estado = 'bg-warning'; break;
                                    case 'vencida': $clase_estado = 'bg-danger'; break;
                                    default: $clase_estado = 'bg-info';
                                }
                                
                                $clase_alerta = '';
                                switch($cuenta['estado_alerta']) {
                                    case 'VENCIDA': $clase_alerta = 'text-danger fw-bold'; break;
                                    case 'POR VENCER': $clase_alerta = 'text-warning fw-bold'; break;
                                    default: $clase_alerta = 'text-success';
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cuenta['numero_factura']) ?></td>
                                    <td><?= htmlspecialchars($cuenta['cliente_nombre']) ?></td>
                                    <td><?= $cuenta['cliente_rtn'] ?? 'N/A' ?></td>
                                    <td>L. <?= number_format($cuenta['monto_total'], 2) ?></td>
                                    <td>L. <?= number_format($cuenta['monto_pagado'], 2) ?></td>
                                    <td class="fw-bold">L. <?= number_format($cuenta['saldo_pendiente'], 2) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cuenta['fecha_emision'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cuenta['fecha_vencimiento'])) ?></td>
                                    <td class="<?= $cuenta['dias_restantes'] < 0 ? 'text-danger fw-bold' : '' ?>">
                                        <?= $cuenta['dias_restantes'] ?> días
                                    </td>
                                    <td><span class="badge <?= $clase_estado ?>"><?= ucfirst($cuenta['estado']) ?></span></td>
                                    <td class="<?= $clase_alerta ?>"><?= $cuenta['estado_alerta'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success" onclick="registrarPago(<?= $cuenta['id'] ?>, '<?= htmlspecialchars($cuenta['numero_factura']) ?>', <?= $cuenta['saldo_pendiente'] ?>)">
                                            <i class="bi bi-cash"></i> Pagar
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="verDetalle(<?= $cuenta['id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">No hay cuentas por cobrar registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Cuenta por Cobrar -->
<div class="modal fade" id="modalNuevaCuenta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nueva Cuenta por Cobrar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaCuenta" method="POST" action="procesar_cuenta.php">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Factura</label>
                        <select name="factura_id" id="factura_id" class="form-select" required>
                            <option value="">-- Seleccione una factura --</option>
                            <?php
                            $facturas_sql = "SELECT f.id, f.numero_factura, f.total, cl.nombre as cliente 
                                            FROM facturas f 
                                            INNER JOIN clientes cl ON f.cliente_id = cl.id 
                                            WHERE f.estado = 'pagada' 
                                            AND f.es_credito = 0
                                            AND f.cuenta_por_cobrar_id IS NULL
                                            ORDER BY f.fecha_emision DESC";
                            $facturas = $conexion->query($facturas_sql);
                            while ($fac = $facturas->fetch_assoc()):
                            ?>
                                <option value="<?= $fac['id'] ?>" data-total="<?= $fac['total'] ?>" data-cliente="<?= htmlspecialchars($fac['cliente']) ?>">
                                    <?= htmlspecialchars($fac['numero_factura']) ?> - <?= htmlspecialchars($fac['cliente']) ?> - L. <?= number_format($fac['total'], 2) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Emisión</label>
                            <input type="date" name="fecha_emision" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="3" placeholder="Observaciones adicionales"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        El monto total se obtendrá automáticamente de la factura seleccionada.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('formNuevaCuenta').submit()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash"></i> Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPago" method="POST" action="procesar_cuenta.php">
                    <input type="hidden" name="accion" value="pagar">
                    <input type="hidden" name="cuenta_id" id="pago_cuenta_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Factura</label>
                        <input type="text" id="pago_numero_factura" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo Pendiente</label>
                        <input type="text" id="pago_saldo_pendiente" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Monto a Pagar <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto_pago" id="monto_pago" class="form-control" required min="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Referencia de Pago</label>
                        <input type="text" name="referencia_pago" class="form-control" placeholder="No. transacción, cheque, etc.">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="document.getElementById('formPago').submit()">
                    <i class="bi bi-check-circle"></i> Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function registrarPago(cuentaId, numeroFactura, saldoPendiente) {
    document.getElementById('pago_cuenta_id').value = cuentaId;
    document.getElementById('pago_numero_factura').value = numeroFactura;
    document.getElementById('pago_saldo_pendiente').value = 'L. ' + saldoPendiente.toFixed(2);
    document.getElementById('monto_pago').max = saldoPendiente;
    
    var modalPago = new bootstrap.Modal(document.getElementById('modalPago'));
    modalPago.show();
}

function verDetalle(cuentaId) {
    // Redirigir a página de detalle o mostrar modal con historial de pagos
    window.location.href = 'detalle_cuenta.php?id=' + cuentaId;
}

// Auto-completar monto máximo al hacer focus
document.getElementById('monto_pago').addEventListener('focus', function() {
    if (!this.value) {
        this.value = this.max;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

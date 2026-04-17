<?php
/**
 * Módulo: Cuentas por Cobrar - Detalle de Cuenta
 * Sistema de Gestión para Salón de Belleza
 */

session_start();
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::verificarSesion();
Auth::verificarRol(['admin', 'cajero']);

$db = Database::getInstance();
$conexion = $db->getConexion();

$cuenta_id = (int)($_GET['id'] ?? 0);

if ($cuenta_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos de la cuenta
$stmt = $conexion->prepare("SELECT * FROM v_cuentas_por_cobrar_resumen WHERE id = ?");
$stmt->bind_param("i", $cuenta_id);
$stmt->execute();
$cuenta = $stmt->get_result()->fetch_assoc();

if (!$cuenta) {
    $_SESSION['error'] = 'Cuenta por cobrar no encontrada';
    header('Location: index.php');
    exit;
}

// Obtener historial de pagos
$stmt_pagos = $conexion->prepare("
    SELECT cpp.*, u.nombre_completo as usuario_nombre
    FROM cuentas_por_cobrar_pagos cpp
    INNER JOIN usuarios u ON cpp.usuario_id = u.id
    WHERE cpp.cuenta_por_cobrar_id = ?
    ORDER BY cpp.fecha_pago DESC
");
$stmt_pagos->bind_param("i", $cuenta_id);
$stmt_pagos->execute();
$pagos = $stmt_pagos->get_result();

include '../../includes/template.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-receipt"></i> Detalle de Cuenta por Cobrar</h2>
                <div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <?php if ($cuenta['saldo_pendiente'] > 0): ?>
                        <button class="btn btn-success" onclick="registrarPago()">
                            <i class="bi bi-cash"></i> Registrar Pago
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Información de la Cuenta
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Factura:</th>
                            <td><?= htmlspecialchars($cuenta['numero_factura']) ?></td>
                        </tr>
                        <tr>
                            <th>Cliente:</th>
                            <td><?= htmlspecialchars($cuenta['cliente_nombre']) ?></td>
                        </tr>
                        <tr>
                            <th>RTN:</th>
                            <td><?= $cuenta['cliente_rtn'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Fecha Emisión:</th>
                            <td><?= date('d/m/Y', strtotime($cuenta['fecha_emision'])) ?></td>
                        </tr>
                        <tr>
                            <th>Fecha Vencimiento:</th>
                            <td class="<?= $cuenta['dias_restantes'] < 0 ? 'text-danger fw-bold' : '' ?>">
                                <?= date('d/m/Y', strtotime($cuenta['fecha_vencimiento'])) ?>
                                (<?= $cuenta['dias_restantes'] ?> días)
                            </td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="badge bg-<?= $cuenta['estado'] === 'pagada' ? 'success' : ($cuenta['estado'] === 'vencida' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($cuenta['estado']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Alerta:</th>
                            <td class="<?= $cuenta['estado_alerta'] === 'VENCIDA' ? 'text-danger fw-bold' : '' ?>">
                                <?= $cuenta['estado_alerta'] ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-calculator"></i> Resumen Financiero
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Monto Total</label>
                        <h3 class="text-primary">L. <?= number_format($cuenta['monto_total'], 2) ?></h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Pagado</label>
                        <h4 class="text-success">L. <?= number_format($cuenta['monto_pagado'], 2) ?></h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Pendiente</label>
                        <h2 class="<?= $cuenta['saldo_pendiente'] > 0 ? 'text-danger' : 'text-success' ?>">
                            L. <?= number_format($cuenta['saldo_pendiente'], 2) ?>
                        </h2>
                    </div>
                    
                    <!-- Barra de Progreso -->
                    <?php 
                    $porcentaje_pagado = ($cuenta['monto_total'] > 0) 
                        ? ($cuenta['monto_pagado'] / $cuenta['monto_total']) * 100 
                        : 0;
                    ?>
                    <div class="mt-4">
                        <label class="form-label">Progreso de Pago</label>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $porcentaje_pagado ?>%;" 
                                 aria-valuenow="<?= $porcentaje_pagado ?>" aria-valuemin="0" aria-valuemax="100">
                                <?= number_format($porcentaje_pagado, 1) ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notas -->
    <?php if (!empty($cuenta['notas'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-sticky"></i> Notas
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($cuenta['notas'])) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial de Pagos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Historial de Pagos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Usuario</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pagos->num_rows > 0): ?>
                                    <?php while ($pago = $pagos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></td>
                                            <td class="text-success fw-bold">L. <?= number_format($pago['monto_pago'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= ucfirst($pago['metodo_pago']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($pago['referencia_pago'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($pago['usuario_nombre']) ?></td>
                                            <td><?= htmlspecialchars($pago['notas'] ?? '') ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            No hay pagos registrados
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Pago (desde detalle) -->
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
                    <input type="hidden" name="cuenta_id" value="<?= $cuenta_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo Pendiente</label>
                        <input type="text" class="form-control" value="L. <?= number_format($cuenta['saldo_pendiente'], 2) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Monto a Pagar <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto_pago" id="monto_pago" class="form-control" required min="0.01" max="<?= $cuenta['saldo_pendiente'] ?>">
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
function registrarPago() {
    var modalPago = new bootstrap.Modal(document.getElementById('modalPago'));
    modalPago.show();
}

// Auto-completar monto máximo al hacer focus
document.getElementById('monto_pago').addEventListener('focus', function() {
    if (!this.value) {
        this.value = this.max;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

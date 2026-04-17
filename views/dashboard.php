<div class="row">
    <!-- Estadísticas Principales -->
    <div class="col-12 mb-4">
        <h5><i class="fas fa-chart-line me-2"></i>Resumen del Día</h5>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon primary">
                <i class="fas fa-cash-register"></i>
            </div>
            <h6 class="text-muted">Ventas Hoy</h6>
            <h3><?php echo formatCurrency($ventasHoy['total_vendido'] ?? 0); ?></h3>
            <small class="text-muted"><?php echo $ventasHoy['total_facturas'] ?? 0; ?> facturas</small>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon success">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h6 class="text-muted">Citas Hoy</h6>
            <h3><?php echo $citasHoy['total_citas'] ?? 0; ?></h3>
            <small class="text-muted">Servicios programados</small>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon warning">
                <i class="fas fa-boxes"></i>
            </div>
            <h6 class="text-muted">Stock Bajo</h6>
            <h3><?php echo count($stockBajo); ?></h3>
            <small class="text-muted">Productos por reordenar</small>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon danger">
                <i class="fas fa-users"></i>
            </div>
            <h6 class="text-muted">Clientes Activos</h6>
            <?php
            $totalClientes = $db->fetchOne("SELECT COUNT(*) as total FROM clientes WHERE estado = 1");
            ?>
            <h3><?php echo $totalClientes['total'] ?? 0; ?></h3>
            <small class="text-muted">En base de datos</small>
        </div>
    </div>
</div>

<!-- Alertas de Stock Bajo -->
<?php if (count($stockBajo) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning alert-stock" role="alert">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Alerta de Stock Bajo</h6>
            <ul class="mb-0 mt-2">
                <?php foreach (array_slice($stockBajo, 0, 5) as $producto): ?>
                <li>
                    <strong><?php echo sanitizeInput($producto['nombre']); ?></strong> - 
                    Stock actual: <?php echo $producto['stock_actual']; ?> / 
                    Mínimo: <?php echo $producto['stock_minimo']; ?>
                    <?php if ($producto['proveedor_nombre']): ?>
                        (Proveedor: <?php echo sanitizeInput($producto['proveedor_nombre']); ?>)
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo APP_URL; ?>index.php?page=inventario&accion=listar" class="btn btn-sm btn-warning mt-2">
                Ver Inventario Completo
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Gráficos y Tablas -->
<div class="row">
    <!-- Últimas Facturas -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Últimas Ventas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimasFacturas)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No hay ventas registradas hoy
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($ultimasFacturas as $factura): ?>
                                <tr>
                                    <td>
                                        <small class="fw-bold"><?php echo sanitizeInput($factura['numero_factura']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo formatDateTime($factura['fecha_emision']); ?></small>
                                    </td>
                                    <td><?php echo sanitizeInput($factura['cliente_nombre'] ?? 'Consumidor Final'); ?></td>
                                    <td><?php echo formatCurrency($factura['total']); ?></td>
                                    <td>
                                        <span class="badge bg-success">Pagada</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="<?php echo APP_URL; ?>index.php?page=facturacion&accion=listar" class="btn btn-sm btn-outline-primary">
                    Ver Todas las Facturas <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Top Servicios -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Servicios Más Populares (Este Mes)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($topServicios)): ?>
                <p class="text-muted text-center py-4">No hay datos de servicios este mes</p>
                <?php else: ?>
                    <canvas id="topServiciosChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="row mt-4">
    <div class="col-12">
        <h5><i class="fas fa-bolt me-2"></i>Accesos Rápidos</h5>
    </div>
    
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=pos" class="btn btn-primary w-100 py-3">
            <i class="fas fa-cash-register fa-2x mb-2"></i>
            <br>Nueva Venta
        </a>
    </div>
    
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=citas&accion=nueva" class="btn btn-success w-100 py-3">
            <i class="fas fa-calendar-plus fa-2x mb-2"></i>
            <br>Nueva Cita
        </a>
    </div>
    
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=clientes&accion=nuevo" class="btn btn-info w-100 py-3">
            <i class="fas fa-user-plus fa-2x mb-2"></i>
            <br>Nuevo Cliente
        </a>
    </div>
    
    <?php if ($auth->hasRole('admin')): ?>
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=inventario&accion=nuevo" class="btn btn-warning w-100 py-3">
            <i class="fas fa-box fa-2x mb-2"></i>
            <br>Nuevo Producto
        </a>
    </div>
    
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=servicios&accion=nuevo" class="btn btn-secondary w-100 py-3">
            <i class="fas fa-cut fa-2x mb-2"></i>
            <br>Nuevo Servicio
        </a>
    </div>
    
    <div class="col-md-2 col-sm-4 mb-3">
        <a href="<?php echo APP_URL; ?>index.php?page=reportes" class="btn btn-dark w-100 py-3">
            <i class="fas fa-chart-bar fa-2x mb-2"></i>
            <br>Reportes
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($topServicios)): ?>
<script>
// Gráfico de Top Servicios
const ctx = document.getElementById('topServiciosChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($topServicios as $s) echo "'" . addslashes($s['nombre']) . "',"; ?>],
        datasets: [{
            label: 'Veces Vendido',
            data: [<?php foreach ($topServicios as $s) echo $s['veces_vendido'] . ','; ?>],
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderColor: [
                'rgba(102, 126, 234, 1)',
                'rgba(118, 75, 162, 1)',
                'rgba(40, 167, 69, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
<?php endif; ?>

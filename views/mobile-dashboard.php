<?php
/**
 * Dashboard móvil - Vista optimizada para dispositivos móviles
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

// Conexión a base de datos
require_once '../../config/config.php';
$db = Database::getInstance();
$conexion = $db->getConnection();

// Obtener estadísticas del día
$query = "SELECT 
    COUNT(*) as total_ventas,
    COALESCE(SUM(total), 0) as monto_total,
    COUNT(DISTINCT cliente_id) as clientes_atendidos
    FROM facturas 
    WHERE DATE(fecha_emision) = CURDATE() AND estado = 'pagada'";
$result = $conexion->query($query);
$stats = $result->fetch_assoc();

// Servicios más vendidos hoy
$query_servicios = "SELECT s.nombre, COUNT(df.item_id) as cantidad
    FROM detalle_factura df
    JOIN servicios s ON df.item_id = s.id
    WHERE df.tipo_item = 'servicio' 
    AND DATE((SELECT fecha_emision FROM facturas WHERE id = df.factura_id)) = CURDATE()
    GROUP BY df.item_id, s.nombre
    ORDER BY cantidad DESC
    LIMIT 5";
$result_servicios = $conexion->query($query_servicios);

// Alertas de stock bajo
$query_stock = "SELECT nombre, stock_actual, stock_minimo 
    FROM productos 
    WHERE stock_actual <= stock_minimo AND estado = 1
    LIMIT 5";
$result_stock = $conexion->query($query_stock);
$stock_bajo = $result_stock->fetch_all(MYSQLI_ASSOC);

// Citas del día
$query_citas = "SELECT c.fecha_hora, cl.nombre as cliente, s.nombre as servicio, u.nombre_completo as estilista
    FROM citas c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN servicios s ON c.servicio_id = s.id
    JOIN usuarios u ON c.estilista_id = u.id
    WHERE DATE(c.fecha_hora) = CURDATE()
    AND c.estado = 'pendiente'
    ORDER BY c.fecha_hora";
$result_citas = $conexion->query($query_citas);
?>

<div class="dashboard-grid">
    <!-- Tarjeta de Ventas -->
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-cash-register"></i>
        </div>
        <div class="stat-info">
            <h3>Ventas Hoy</h3>
            <p>L. <?php echo number_format($stats['monto_total'], 2); ?></p>
            <small><?php echo $stats['total_ventas']; ?> transacciones</small>
        </div>
    </div>

    <!-- Tarjeta de Clientes -->
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Clientes Atendidos</h3>
            <p><?php echo $stats['clientes_atendidos']; ?></p>
            <small>Día de hoy</small>
        </div>
    </div>

    <!-- Tarjeta de Citas -->
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-info">
            <h3>Citas Pendientes</h3>
            <p><?php echo $result_citas->num_rows; ?></p>
            <small>Para hoy</small>
        </div>
    </div>

    <!-- Tarjeta de Stock Crítico -->
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h3>Stock Bajo</h3>
            <p><?php echo count($stock_bajo); ?></p>
            <small>Productos críticos</small>
        </div>
    </div>
</div>

<!-- Servicios Más Vendidos -->
<div class="card-mobile mt-3">
    <h3><i class="fas fa-chart-bar"></i> Servicios Más Vendidos</h3>
    <?php if ($result_servicios->num_rows > 0): ?>
        <div class="table-responsive-mobile">
            <table class="table-mobile">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th class="text-right">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($servicio = $result_servicios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                            <td class="text-right">
                                <span class="badge bg-primary"><?php echo $servicio['cantidad']; ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted text-center py-3">No hay servicios vendidos hoy</p>
    <?php endif; ?>
</div>

<!-- Citas Programadas -->
<div class="card-mobile mt-3">
    <h3><i class="fas fa-clock"></i> Próximas Citas</h3>
    <?php if ($result_citas->num_rows > 0): ?>
        <div class="list-group list-group-flush">
            <?php while($cita = $result_citas->fetch_assoc()): 
                $hora = date('h:i A', strtotime($cita['fecha_hora']));
            ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo htmlspecialchars($cita['cliente']); ?></strong>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-cut"></i> <?php echo htmlspecialchars($cita['servicio']); ?>
                        </small>
                    </div>
                    <div class="text-right">
                        <span class="badge bg-info"><?php echo $hora; ?></span>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($cita['estilista']); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="text-muted text-center py-3">No hay citas pendientes para hoy</p>
    <?php endif; ?>
</div>

<!-- Alertas de Stock -->
<?php if (count($stock_bajo) > 0): ?>
<div class="card-mobile mt-3">
    <h3><i class="fas fa-exclamation-circle text-danger"></i> Productos con Stock Bajo</h3>
    <div class="list-group list-group-flush">
        <?php foreach($stock_bajo as $producto): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
                <span class="badge bg-danger">
                    <?php echo $producto['stock_actual']; ?> / <?php echo $producto['stock_minimo']; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-warning btn-touch w-100 mt-2" onclick="navigateTo('inventory')">
        <i class="fas fa-boxes"></i> Ver Inventario Completo
    </button>
</div>
<?php endif; ?>

<!-- Accesos Rápidos -->
<div class="card-mobile mt-3">
    <h3><i class="fas fa-bolt"></i> Accesos Rápidos</h3>
    <div class="row g-2">
        <div class="col-6">
            <button class="btn btn-primary btn-touch w-100" onclick="navigateTo('pos')">
                <i class="fas fa-cash-register"></i><br>
                <small>Nueva Venta</small>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-success btn-touch w-100" onclick="navigateTo('appointments')">
                <i class="fas fa-calendar-plus"></i><br>
                <small>Agendar Cita</small>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-info btn-touch w-100" onclick="navigateTo('clients')">
                <i class="fas fa-user-plus"></i><br>
                <small>Nuevo Cliente</small>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-secondary btn-touch w-100" onclick="navigateTo('reports')">
                <i class="fas fa-chart-line"></i><br>
                <small>Reportes</small>
            </button>
        </div>
    </div>
</div>

<!-- Gráfica de Ventas Semanales -->
<div class="card-mobile mt-3">
    <h3><i class="fas fa-chart-area"></i> Ventas de la Semana</h3>
    <canvas id="weeklySalesChart" height="200"></canvas>
</div>

<script>
// Gráfica de ventas semanales
document.addEventListener('DOMContentLoaded', function() {
    fetch('../../api/weekly_sales.php')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('weeklySalesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Ventas (L.)',
                        data: data.values,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'L. ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error al cargar gráfica:', error));
});
</script>

<!DOCTYPE html>
<html lang="es-HN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6f42c1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Salón HN">
    <title>Salón Belleza - Gestión Móvil</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="./manifest.json">
    
    <!-- Iconos para iOS -->
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p class="mt-2">Cargando...</p>
    </div>

    <!-- Header -->
    <header class="bg-primary text-white py-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0"><i class="fas fa-spa"></i> Salón Belleza</h1>
                    <small>Sistema de Gestión</small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <span id="user-name">Usuario</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="modules/login/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main id="app-content" class="pb-5">
        <!-- El contenido se carga dinámicamente según el módulo -->
        <div class="container-fluid mt-3">
            <?php
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                header('Location: modules/login/index.php');
                exit;
            }
            
            // Detectar si es dispositivo móvil
            $isMobile = preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $_SERVER['HTTP_USER_AGENT']);
            
            // Mostrar dashboard por defecto
            include 'views/mobile-dashboard.php';
            ?>
        </div>
    </main>

    <!-- Carrito Flotante (solo en POS) -->
    <div id="cart-float" class="cart-float" style="display: none;" onclick="navigateTo('cart')">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" style="display: none;">0</span>
        <span class="cart-total">L. 0.00</span>
    </div>

    <!-- Navegación Móvil Inferior -->
    <nav class="mobile-nav">
        <a href="#" class="active" data-module="dashboard">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="#" data-module="pos">
            <i class="fas fa-cash-register"></i>
            <span>POS</span>
        </a>
        <a href="#" data-module="cart">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrito</span>
            <span class="badge bg-danger nav-cart-badge" style="display: none;">0</span>
        </a>
        <a href="#" data-module="inventory">
            <i class="fas fa-boxes"></i>
            <span>Inventario</span>
        </a>
        <a href="#" data-module="appointments">
            <i class="fas fa-calendar-alt"></i>
            <span>Citas</span>
        </a>
    </nav>

    <!-- Modal de Pago -->
    <div id="modal-payment" class="modal-touch">
        <div class="modal-content-touch">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Procesar Pago</h3>
                <button type="button" class="btn-close" onclick="hideModal('payment')"></button>
            </div>
            
            <form id="payment-form">
                <div class="mb-3">
                    <label class="form-label">Cliente</label>
                    <select class="form-control-touch" id="cliente-id" required>
                        <option value="">Consumidor Final</option>
                        <!-- Se llena dinámicamente -->
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Método de Pago</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="payment_method" id="pay-cash" value="efectivo" checked>
                        <label class="btn btn-outline-primary" for="pay-cash">
                            <i class="fas fa-money-bill"></i> Efectivo
                        </label>
                        
                        <input type="radio" class="btn-check" name="payment_method" id="pay-card" value="tarjeta">
                        <label class="btn btn-outline-primary" for="pay-card">
                            <i class="fas fa-credit-card"></i> Tarjeta
                        </label>
                        
                        <input type="radio" class="btn-check" name="payment_method" id="pay-transfer" value="transferencia">
                        <label class="btn btn-outline-primary" for="pay-transfer">
                            <i class="fas fa-university"></i> Transferencia
                        </label>
                    </div>
                </div>
                
                <div id="cash-change-container" class="mb-3">
                    <label class="form-label">Pago con</label>
                    <input type="number" class="form-control-touch" id="payment-amount" placeholder="L. 0.00" step="0.01">
                    <small class="text-muted" id="change-info">Cambio: L. 0.00</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control-touch" id="payment-notes" rows="2"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success btn-touch w-100">
                    <i class="fas fa-check-circle"></i> Confirmar Pago
                </button>
            </form>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 3000;"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
    
    <!-- App JavaScript -->
    <script src="assets/js/app.js"></script>
    
    <!-- Scripts específicos de la página -->
    <script>
    // Inicializar datos del usuario
    document.addEventListener('DOMContentLoaded', function() {
        const userName = '<?php echo isset($_SESSION['nombre_completo']) ? htmlspecialchars($_SESSION['nombre_completo']) : "Usuario"; ?>';
        document.getElementById('user-name').textContent = userName;
        
        // Mostrar carrito flotante solo en POS
        if (currentModule === 'pos') {
            document.getElementById('cart-float').style.display = 'flex';
        }
        
        // Calcular cambio en tiempo real
        const paymentAmount = document.getElementById('payment-amount');
        if (paymentAmount) {
            paymentAmount.addEventListener('input', function() {
                const total = calculateTotal();
                const paid = parseFloat(this.value) || 0;
                const change = paid - total;
                
                document.getElementById('change-info').textContent = 
                    change >= 0 ? `Cambio: L. ${formatNumber(change)}` : 'Falta: L. ' + formatNumber(Math.abs(change));
            });
        }
    });
    
    // Manejar envío del formulario de pago
    document.getElementById('payment-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const clientId = document.getElementById('cliente-id').value;
        
        // Validar que haya suficiente dinero si es efectivo
        if (paymentMethod === 'efectivo') {
            const total = calculateTotal();
            const paid = parseFloat(document.getElementById('payment-amount').value) || 0;
            
            if (paid < total) {
                showError('El monto en efectivo es insuficiente');
                return;
            }
        }
        
        // Procesar pago vía AJAX
        fetch('modules/pos/process_sale.php', {
            method: 'POST',
            body: JSON.stringify({
                cart: cart,
                payment_method: paymentMethod,
                client_id: clientId,
                notes: document.getElementById('payment-notes').value
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('¡Venta procesada exitosamente!');
                
                // Generar factura PDF
                if (data.invoice_id) {
                    window.open(`modules/invoices/generate_pdf.php?id=${data.invoice_id}`, '_blank');
                }
                
                // Limpiar carrito y cerrar modal
                clearCart();
                hideModal('payment');
                navigateTo('dashboard');
            } else {
                showError(data.message || 'Error al procesar la venta');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error de conexión. Intente nuevamente.');
        });
    });
    </script>
</body>
</html>

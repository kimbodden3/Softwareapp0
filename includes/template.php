<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand i {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }
        
        .sidebar-menu a i {
            width: 25px;
            margin-right: 10px;
        }
        
        .sidebar-menu .submenu {
            list-style: none;
            padding-left: 20px;
            display: none;
        }
        
        .sidebar-menu .submenu.show {
            display: block;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card-icon.primary {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
        }
        
        .stat-card-icon.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .stat-card-icon.warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .stat-card-icon.danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Alerts */
        .alert-stock {
            border-left: 4px solid #dc3545;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Badges */
        .badge-role {
            background: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-spa"></i>
            <h5><?php echo APP_NAME; ?></h5>
            <small>v<?php echo APP_VERSION; ?></small>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=dashboard" class="<?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            
            <?php if ($auth->hasRole(['admin', 'cajero'])): ?>
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=pos" class="<?php echo ($currentPage ?? '') === 'pos' ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register"></i> Punto de Venta
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($auth->hasRole('admin')): ?>
            <li>
                <a href="#" data-bs-toggle="collapse" data-bs-target="#ventasSubmenu">
                    <i class="fas fa-chart-line"></i> Ventas
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <ul class="submenu collapse" id="ventasSubmenu">
                    <li><a href="<?php echo APP_URL; ?>index.php?page=facturacion&accion=listar"><i class="fas fa-file-invoice"></i> Facturas</a></li>
                    <li><a href="<?php echo APP_URL; ?>index.php?page=reportes&tipo=ventas"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                </ul>
            </li>
            
            <li>
                <a href="#" data-bs-toggle="collapse" data-bs-target="#inventarioSubmenu">
                    <i class="fas fa-boxes"></i> Inventario
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <ul class="submenu collapse" id="inventarioSubmenu">
                    <li><a href="<?php echo APP_URL; ?>index.php?page=inventario&accion=listar"><i class="fas fa-list"></i> Productos</a></li>
                    <li><a href="<?php echo APP_URL; ?>index.php?page=inventario&accion=movimientos"><i class="fas fa-exchange-alt"></i> Movimientos</a></li>
                    <li><a href="<?php echo APP_URL; ?>index.php?page=inventario&accion=ajustes"><i class="fas fa-edit"></i> Ajustes</a></li>
                </ul>
            </li>
            
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=proveedores">
                    <i class="fas fa-truck"></i> Proveedores
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="#" data-bs-toggle="collapse" data-bs-target="#serviciosSubmenu">
                    <i class="fas fa-cut"></i> Servicios
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <ul class="submenu collapse" id="serviciosSubmenu">
                    <li><a href="<?php echo APP_URL; ?>index.php?page=servicios&accion=listar"><i class="fas fa-list"></i> Catálogo</a></li>
                    <li><a href="<?php echo APP_URL; ?>index.php?page=categorias&accion=listar"><i class="fas fa-tags"></i> Categorías</a></li>
                </ul>
            </li>
            
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=citas">
                    <i class="fas fa-calendar-alt"></i> Citas
                </a>
            </li>
            
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=clientes">
                    <i class="fas fa-users"></i> Clientes
                </a>
            </li>
            
            <?php if ($auth->hasRole('admin')): ?>
            <li>
                <a href="<?php echo APP_URL; ?>index.php?page=usuarios">
                    <i class="fas fa-user-shield"></i> Usuarios
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="<?php echo APP_URL; ?>modules/login/logout.php" onclick="return confirm('¿Está seguro de cerrar sesión?')">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div>
                <h4 class="mb-0"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
                <small class="text-muted"><?php echo formatDate(date('Y-m-d')); ?></small>
            </div>
            
            <div class="user-info">
                <div class="text-end d-none d-md-block">
                    <strong><?php echo sanitizeInput($currentUser['nombre_completo']); ?></strong>
                    <br>
                    <span class="badge-role"><?php echo ucfirst($currentUser['rol']); ?></span>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser['nombre_completo'], 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <?php
            $flashMessage = getFlashMessage();
            if ($flashMessage):
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php echo sanitizeInput($flashMessage['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php echo $content; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar en móvil
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Auto-hide alerts después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>

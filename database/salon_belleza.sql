-- ============================================
-- SISTEMA DE GESTIÓN PARA SALÓN DE BELLEZA
-- BASE DE DATOS MySQL 8.0
-- Honduras - UTF8MB4
-- ============================================

CREATE DATABASE IF NOT EXISTS salon_belleza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE salon_belleza;

-- ============================================
-- TABLA: USUARIOS
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cajero', 'estilista') NOT NULL DEFAULT 'cajero',
    estado TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueo_hasta DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CLIENTES
-- ============================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    rtn VARCHAR(14) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    direccion TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado TINYINT(1) DEFAULT 1,
    INDEX idx_rtn (rtn),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CATEGORÍAS DE SERVICIOS
-- ============================================
CREATE TABLE categorias_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,
    estado TINYINT(1) DEFAULT 1,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: SERVICIOS
-- ============================================
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL,
    duracion_minutos INT DEFAULT 30,
    estado TINYINT(1) DEFAULT 1,
    FOREIGN KEY (categoria_id) REFERENCES categorias_servicios(id) ON DELETE RESTRICT,
    INDEX idx_categoria (categoria_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: PROVEEDORES
-- ============================================
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(150) NOT NULL,
    rtn_proveedor VARCHAR(14) NOT NULL,
    contacto VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    direccion TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado TINYINT(1) DEFAULT 1,
    INDEX idx_rtn (rtn_proveedor),
    INDEX idx_nombre (nombre_empresa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: PRODUCTOS
-- ============================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    categoria VARCHAR(50) NULL,
    proveedor_id INT NULL,
    stock_minimo INT DEFAULT 5,
    stock_actual INT DEFAULT 0,
    precio_costo DECIMAL(10,2) NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    foto_path VARCHAR(255) NULL,
    fecha_vencimiento DATE NULL,
    lote VARCHAR(50) NULL,
    estado TINYINT(1) DEFAULT 1,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_barras),
    INDEX idx_nombre (nombre),
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_stock (stock_actual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: INVENTARIO MOVIMIENTOS (KARDEX)
-- ============================================
CREATE TABLE inventario_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste', 'merma', 'devolucion') NOT NULL,
    cantidad INT NOT NULL,
    motivo TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_producto (producto_id),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CONFIGURACIÓN FACTURACIÓN (CAI)
-- ============================================
CREATE TABLE configuracion_cai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    establecimiento VARCHAR(3) DEFAULT '001',
    punto_emision VARCHAR(2) DEFAULT '01',
    cai VARCHAR(50) NOT NULL,
    rango_desde BIGINT NOT NULL,
    rango_hasta BIGINT NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: FACTURAS
-- ============================================
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(20) NOT NULL UNIQUE,
    cai VARCHAR(50) NOT NULL,
    cliente_id INT NULL,
    usuario_id INT NOT NULL,
    fecha_emision DATETIME NOT NULL,
    sub_total DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    isv_15 DECIMAL(10,2) DEFAULT 0,
    isv_18 DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') DEFAULT 'efectivo',
    estado ENUM('pagada', 'anulada', 'pendiente') DEFAULT 'pagada',
    motivo_anulacion TEXT NULL,
    usuario_anulacion INT NULL,
    fecha_anulacion DATETIME NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_numero (numero_factura),
    INDEX idx_fecha (fecha_emision),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: DETALLE FACTURA
-- ============================================
CREATE TABLE detalle_factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    tipo_item ENUM('servicio', 'producto') NOT NULL,
    item_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    isv_aplicado DECIMAL(5,2) NOT NULL,
    total_linea DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    INDEX idx_factura (factura_id),
    INDEX idx_tipo (tipo_item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CITAS
-- ============================================
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    servicio_id INT NOT NULL,
    estilista_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    estado ENUM('pendiente', 'completada', 'cancelada', 'no_show') DEFAULT 'pendiente',
    notas TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE RESTRICT,
    FOREIGN KEY (estilista_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha_hora),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estilista (estilista_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: AUDITORÍA LOGIN
-- ============================================
CREATE TABLE auditoria_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    intento_exitoso TINYINT(1) DEFAULT 0,
    fecha_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: RECUPERACIÓN CONTRASEÑA
-- ============================================
CREATE TABLE password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE PRUEBA INICIALES
-- ============================================

-- Usuario Administrador (password: admin123)
INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, estado) VALUES
('Administrador Principal', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Categorías de Servicios
INSERT INTO categorias_servicios (nombre, descripcion) VALUES
('Corte Dama', 'Servicios de corte para damas'),
('Corte Caballero', 'Servicios de corte para caballeros'),
('Tintes', 'Aplicación de tintes y mechas'),
('Manicure', 'Cuidado de uñas de manos'),
('Pedicure', 'Cuidado de uñas de pies'),
('Tratamientos Capilares', 'Tratamientos especializados para el cabello'),
('Maquillaje', 'Servicios de maquillaje profesional');

-- Servicios de Ejemplo
INSERT INTO servicios (categoria_id, nombre, descripcion, precio, duracion_minutos) VALUES
(1, 'Corte Dama Básico', 'Corte simple sin lavado', 150.00, 30),
(1, 'Corte Dama con Lavado', 'Corte incluye lavado y secado', 200.00, 45),
(1, 'Corte Dama con Peinado', 'Corte, lavado y peinado', 250.00, 60),
(2, 'Corte Caballero Clásico', 'Corte tradicional', 100.00, 25),
(2, 'Corte Caballero Moderno', 'Corte con diseño', 120.00, 30),
(3, 'Tinte Completo', 'Aplicación de tinte en todo el cabello', 450.00, 90),
(3, 'Mechas Balayage', 'Técnica de mechas balayage', 800.00, 120),
(3, 'Retoque de Raíz', 'Aplicación de tinte solo en raíz', 250.00, 45),
(4, 'Manicure Básico', 'Limpieza y esmaltado simple', 120.00, 30),
(4, 'Manicure Gel', 'Manicure con esmalte en gel', 200.00, 45),
(4, 'Uñas Acrílicas', 'Aplicación de uñas acrílicas', 350.00, 90),
(5, 'Pedicure Básico', 'Limpieza y esmaltado de pies', 150.00, 40),
(5, 'Pedicure Spa', 'Pedicure completo con masaje', 250.00, 60),
(6, 'Hidratación Profunda', 'Tratamiento hidratante capilar', 300.00, 45),
(6, 'Keratina', 'Alisado con keratina', 1200.00, 180),
(7, 'Maquillaje de Día', 'Maquillaje natural para día', 350.00, 45),
(7, 'Maquillaje de Noche', 'Maquillaje sofisticado para noche', 500.00, 60),
(7, 'Maquillaje de Novia', 'Maquillaje completo para novia', 1500.00, 90);

-- Proveedores de Ejemplo
INSERT INTO proveedores (nombre_empresa, rtn_proveedor, contacto, telefono, email, direccion) VALUES
('Distribuidora de Belleza S.A.', '88991234567890', 'Juan Pérez', '2233-4455', 'juan@distribuidorabeleza.hn', 'Boulevard Morazán, Tegucigalpa'),
('Productos Profesionales HN', '88999876543210', 'María González', '2244-5566', 'maria@productosprofesionales.hn', 'Colonia Palmira, San Pedro Sula'),
('Importadora de Cosméticos', '88995555666677', 'Carlos Rodríguez', '2255-6677', 'carlos@importacosmeticos.hn', 'Barrio El Centro, Tegucigalpa');

-- Productos de Ejemplo
INSERT INTO productos (codigo_barras, nombre, descripcion, categoria, proveedor_id, stock_minimo, stock_actual, precio_costo, precio_venta) VALUES
('7501234567890', 'Shampoo Profesional 500ml', 'Shampoo para todo tipo de cabello', 'Cuidado Capilar', 1, 10, 25, 180.00, 350.00),
('7501234567891', 'Acondicionador Hidratante 500ml', 'Acondicionador con vitamina E', 'Cuidado Capilar', 1, 10, 30, 190.00, 380.00),
('7501234567892', 'Tinte Negro #1', 'Tinte permanente color negro', 'Tintes', 2, 5, 15, 120.00, 250.00),
('7501234567893', 'Tinte Rubio #8', 'Tinte permanente color rubio', 'Tintes', 2, 5, 12, 120.00, 250.00),
('7501234567894', 'Esmalte Rojo Classic', 'Esmalte de uñas color rojo', 'Esmaltes', 3, 20, 50, 45.00, 95.00),
('7501234567895', 'Esmalte Nude Elegance', 'Esmalte tono nude', 'Esmaltes', 3, 20, 45, 45.00, 95.00),
('7501234567896', 'Aceite de Argán 100ml', 'Aceite puro para tratamiento capilar', 'Tratamientos', 1, 8, 20, 280.00, 550.00),
('7501234567897', 'Mascarilla Reparadora 250ml', 'Mascarilla intensiva para cabello dañado', 'Tratamientos', 2, 10, 18, 220.00, 450.00);

-- Configuración CAI (Datos de ejemplo - deben ser actualizados con CAI real del SAR)
INSERT INTO configuracion_cai (cai, rango_desde, rango_hasta, fecha_vencimiento) VALUES
('CAI-EJEMPLO-123456789012345', 1, 1000, '2025-12-31');

-- ============================================
-- VISTAS ÚTILES PARA REPORTES
-- ============================================

-- Vista: Productos con Stock Bajo
CREATE OR REPLACE VIEW v_productos_stock_bajo AS
SELECT p.*, pr.nombre_empresa as proveedor_nombre
FROM productos p
LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
WHERE p.stock_actual <= p.stock_minimo AND p.estado = 1;

-- Vista: Ventas Diarias
CREATE OR REPLACE VIEW v_ventas_diarias AS
SELECT 
    DATE(fecha_emision) as fecha,
    COUNT(*) as total_facturas,
    SUM(total) as total_vendido,
    SUM(isv_15 + isv_18) as total_isv
FROM facturas
WHERE estado = 'pagada'
GROUP BY DATE(fecha_emision)
ORDER BY fecha DESC;

-- Vista: Top Servicios
CREATE OR REPLACE VIEW v_top_servicios AS
SELECT 
    s.nombre as servicio,
    c.nombre as categoria,
    COUNT(df.id) as veces_vendido,
    SUM(df.total_linea) as total_ingresos
FROM detalle_factura df
INNER JOIN servicios s ON df.item_id = s.id AND df.tipo_item = 'servicio'
INNER JOIN categorias_servicios c ON s.categoria_id = c.id
INNER JOIN facturas f ON df.factura_id = f.id
WHERE f.estado = 'pagada'
GROUP BY s.id, c.id
ORDER BY veces_vendido DESC
LIMIT 10;

-- ============================================
-- FIN DEL SCRIPT DE BASE DE DATOS
-- ============================================

# SISTEMA DE GESTIÓN PARA SALÓN DE BELLEZA - HONDURAS

## DESCRIPCIÓN GENERAL

Aplicación web completa de gestión empresarial para salón de belleza en Honduras, que integra POS (Punto de Venta), facturación con normativa hondureña (SAR), control de inventario con gestión de imágenes, administración de proveedores y sistema de reportes gerenciales.

## REQUISITOS DEL SISTEMA

### Servidor
- **PHP:** 8.2 o superior
- **MySQL:** 8.0 o superior
- **Servidor Web:** Apache 2.4+ o Nginx
- **Extensiones PHP requeridas:**
  - mysqli
  - gd o imagick
  - json
  - mbstring
  - zip (para exportación Excel)

### Cliente
- Navegador moderno (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- JavaScript habilitado
- Resolución mínima recomendada: 1280x768

## INSTALACIÓN

### Paso 1: Clonar/Descargar el Proyecto

Coloque los archivos en su directorio web (ej. `/var/www/html/salon_belleza` o `C:/xampp/htdocs/salon_belleza`)

### Paso 2: Configurar Base de Datos

1. Acceda a phpMyAdmin o cliente MySQL
2. Ejecute el script SQL ubicado en `database/salon_belleza.sql`
3. Este script creará:
   - La base de datos `salon_belleza`
   - Todas las tablas necesarias
   - Datos de prueba iniciales

### Paso 3: Configurar Archivo de Conexión

Edite el archivo `config/config.php` y ajuste los parámetros de conexión:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'salon_belleza');
define('DB_USER', 'su_usuario_mysql');
define('DB_PASS', 'su_contraseña_mysql');
```

### Paso 4: Configurar Permisos de Archivos

Asegúrese de que las carpetas de uploads tengan permisos de escritura:

```bash
chmod -R 755 /workspace/uploads
chmod -R 755 /workspace/logs
```

En Windows, asegúrese de que el usuario del servidor web tenga permisos de escritura.

### Paso 5: Configurar URL Base

En `config/config.php`, actualice la URL base según su instalación:

```php
define('APP_URL', 'http://localhost/salon_belleza/');
// o
define('APP_URL', 'https://midominio.com/');
```

### Paso 6: Acceso Inicial

1. Abra su navegador y navegue a: `http://localhost/salon_belleza/`
2. Credenciales de administrador por defecto:
   - **Usuario:** admin
   - **Contraseña:** admin123

**IMPORTANTE:** Cambie la contraseña inmediatamente después del primer acceso.

## ESTRUCTURA DE ARCHIVOS

```
/workspace
├── config/
│   └── config.php              # Configuración principal
├── includes/
│   ├── Database.php            # Clase de conexión a BD
│   ├── Auth.php                # Autenticación y sesiones
│   ├── functions.php           # Funciones utilitarias
│   └── template.php            # Plantilla maestra
├── modules/
│   ├── login/                  # Módulo de autenticación
│   ├── pos/                    # Punto de venta
│   ├── facturacion/            # Facturación
│   ├── inventario/             # Gestión de inventario
│   ├── proveedores/            # Administración de proveedores
│   ├── clientes/               # Gestión de clientes
│   ├── servicios/              # Catálogo de servicios
│   ├── citas/                  # Agenda de citas
│   └── reportes/               # Reportes y estadísticas
├── views/
│   └── dashboard.php           # Vista del panel principal
├── database/
│   └── salon_belleza.sql       # Script de base de datos
├── assets/
│   ├── css/                    # Hojas de estilo adicionales
│   ├── js/                     # Scripts JavaScript
│   └── images/                 # Imágenes del sistema
├── uploads/
│   ├── productos/              # Fotos de productos
│   └── facturas/               # PDFs de facturas
├── logs/
│   └── error.log               # Registro de errores
├── tcpdf/                      # Librería TCPDF (pendiente instalar)
├── phpspreadsheet/             # Librería PhpSpreadsheet (pendiente instalar)
└── index.php                   # Router principal
```

## CARACTERÍSTICAS PRINCIPALES

### 1. Sistema de Login y Seguridad
- Autenticación con hash de contraseñas (bcrypt)
- Bloqueo temporal tras 3 intentos fallidos
- Sesiones con timeout de 30 minutos
- Roles diferenciados: Admin, Cajero, Estilista
- Protección CSRF en formularios
- Auditoría de logins (IP, timestamp)

### 2. Punto de Venta (POS)
- Interfaz rápida tipo touch-friendly
- Búsqueda de servicios por categoría
- Búsqueda de productos por código de barras
- Carrito de compras en tiempo real
- Cálculo automático de impuestos hondureños:
  - ISV 15% para servicios
  - ISV 18% para productos
- Múltiples métodos de pago
- Generación de factura inmediata

### 3. Facturación (Normativa SAR Honduras)
- Numeración correlativa automática
- Soporte para CAI (Código de Autorización de Impresión)
- Formato de factura según requisitos SAR
- Campos obligatorios: RTN, fecha, desglose de impuestos
- Historial de facturas con opción de anulación
- Exportación para declaración mensual

### 4. Gestión de Inventario
- Catálogo de productos con fotografías
- Control de stock mínimo con alertas
- Kardex completo de movimientos
- Ajustes de inventario (mermas, daños)
- Gestión de lotes y vencimientos

### 5. Gestión de Proveedores
- Ficha completa con RTN
- Historial de compras
- Cuentas por pagar

### 6. Reportes y Estadísticas
- Ventas diarias/semanales/mensuales
- Top servicios más solicitados
- Stock actual con indicadores visuales
- Rotación de productos
- Exportación a PDF y Excel

## CONFIGURACIÓN ESPECÍFICA PARA HONDURAS

### Moneda
- Lempira Hondureña (L.)
- Formato: 1,234.56

### Impuestos
- ISV Servicios: 15%
- ISV Productos: 18%

### RTN (Registro Tributario Nacional)
- Validación de formato (14 dígitos)
- Algoritmo de verificación incluido

### Fechas
- Formato: DD/MM/YYYY
- Timezone: America/Tegucigalpa

### Idioma
- Español (Honduras)
- Todos los labels y mensajes localizados

## USUARIOS Y ROLES

### Administrador
- Acceso total al sistema
- Puede anular facturas
- Gestionar usuarios
- Ver todos los reportes
- Configurar parámetros del sistema

### Cajero
- Acceder al punto de venta
- Registrar ventas
- Gestionar clientes
- Ver citas del día

### Estilista
- Ver agenda de citas
- Marcar citas como completadas
- Ver sus clientes asignados

## DATOS DE PRUEBA INCLUIDOS

El script SQL incluye:

### Usuario Admin
- Usuario: admin
- Contraseña: admin123

### Categorías de Servicios
- Corte Dama
- Corte Caballero
- Tintes
- Manicure
- Pedicure
- Tratamientos Capilares
- Maquillaje

### Servicios de Ejemplo (18 servicios)
- Cortes desde L. 100.00
- Tintes desde L. 250.00
- Tratamientos desde L. 300.00
- Maquillajes desde L. 350.00

### Proveedores (3 proveedores)
- Distribuidora de Belleza S.A.
- Productos Profesionales HN
- Importadora de Cosméticos

### Productos (8 productos)
- Shampoos, acondicionadores
- Tintes
- Esmaltes
- Tratamientos capilares

## SEGURIDAD

### Implementado
- Prepared statements contra SQL injection
- password_hash() para contraseñas
- Protección CSRF
- Sanitización de entradas
- Validación de tipos de archivo
- Transacciones SQL para integridad
- Logs de auditoría

### Recomendaciones Adicionales
1. Cambiar contraseñas por defecto inmediatamente
2. Usar HTTPS en producción
3. Configurar firewall de aplicación
4. Realizar backups regulares
5. Mantener PHP y MySQL actualizados

## LIBRERÍAS EXTERNAS REQUERIDAS

### Para Producción
1. **TCPDF** - Generación de PDFs (facturas)
   ```bash
   composer require tecnickcom/tcpdf
   ```

2. **PhpSpreadsheet** - Exportación a Excel
   ```bash
   composer require phpoffice/phpspreadsheet
   ```

### CDN Incluidos
- Bootstrap 5.3.0 (CSS y JS)
- Font Awesome 6.4.0 (iconos)
- Chart.js 4.4.0 (gráficos)

## SOPORTE TÉCNICO

### Logs de Errores
Los errores se registran en `logs/error.log`

### Modo Debug
Para desarrollo, active display_errors en config.php:
```php
ini_set('display_errors', 1);
```

Para producción, desactívelo:
```php
ini_set('display_errors', 0);
```

## ACTUALIZACIÓN DEL CAI

Para actualizar el CAI de facturación:

1. Vaya a Administración > Configuración > CAI
2. Ingrese los datos proporcionados por el SAR:
   - Código CAI
   - Rango desde/hasta
   - Fecha de vencimiento
3. Guarde los cambios

## BACKUP DE BASE DE DATOS

### Manual desde phpMyAdmin
1. Seleccione la base de datos `salon_belleza`
2. Click en "Exportar"
3. Elija formato SQL
4. Descargue el archivo

### Programado (Linux)
```bash
#!/bin/bash
mysqldump -u root -p salon_belleza > /backups/salon_$(date +%Y%m%d).sql
```

## LICENCIA

Este software es propiedad privada. Todos los derechos reservados.

## VERSIÓN

Versión actual: 1.0.0
Fecha de lanzamiento: 2024

---

**Desarrollado para Salón de Belleza - Honduras**
**Cumple con normativas SAR vigentes**

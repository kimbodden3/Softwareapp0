# GUÍA DE INSTALACIÓN - VERSIÓN MÓVIL

## 📱 CARACTERÍSTICAS MÓVILES IMPLEMENTADAS

### Progressive Web App (PWA)
La aplicación ahora es completamente adaptable para dispositivos móviles con las siguientes características:

#### 1. **Manifiesto PWA** (`manifest.json`)
- Nombre de la app: "Salón Belleza HN"
- Iconos personalizados
- Modo standalone (se comporta como app nativa)
- Color de tema: #6f42c1 (morado corporativo)
- Orientación: portrait-primary

#### 2. **Service Worker** (`sw.js`)
- Caché de recursos para funcionamiento offline parcial
- Estrategia: Cache First, luego Network
- Actualización automática de caché
- Fallback para cuando no hay conexión

#### 3. **Diseño Mobile-First** (`assets/css/styles.css`)
- Navegación inferior tipo app móvil
- Botones touch-friendly (mínimo 48x48px)
- Cards optimizadas para pantallas pequeñas
- Modales tipo bottom sheet en móviles
- Grid responsive para POS (2 columnas en móvil)
- Carrito flotante con animación
- Safe area para dispositivos con notch (iPhone X+)

#### 4. **JavaScript Móvil** (`assets/js/app.js`)
- Navegación SPA (Single Page Application)
- Carga dinámica de módulos vía AJAX
- Gestión del carrito en sessionStorage
- Interacciones táctiles mejoradas
- Prevención de zoom double-tap en iOS
- Manejo del botón atrás en Android
- Detección online/offline
- Notificaciones toast

#### 5. **Interfaz Móvil** (`mobile.php`)
- Header fijo con menú de usuario
- Navegación inferior con 5 módulos:
  - 🏠 Inicio (Dashboard)
  - 💰 POS (Punto de Venta)
  - 🛒 Carrito
  - 📦 Inventario
  - 📅 Citas
- Modal de pago optimizado para touch
- Carrito flotante accesible

#### 6. **Dashboard Móvil** (`views/mobile-dashboard.php`)
- Tarjetas de estadísticas con iconos
- Servicios más vendidos del día
- Próximas citas programadas
- Alertas de stock bajo
- Accesos rápidos a funciones principales
- Gráfica de ventas semanales (Chart.js)

---

## 🚀 INSTALACIÓN EN DISPOSITIVO MÓVIL

### Android (Chrome)
1. Abrir la aplicación en Chrome
2. Tocar menú (⋮) > "Agregar a la pantalla principal"
3. Confirmar nombre y tocar "Agregar"
4. La app aparecerá en el launcher como una app nativa

### iOS (Safari)
1. Abrir la aplicación en Safari
2. Tocar botón Compartir (cuadrado con flecha)
3. Seleccionar "Agregar al inicio"
4. Confirmar y tocar "Agregar"
5. La app aparecerá en el home screen

### Desktop (Chrome/Edge)
1. Abrir la aplicación
2. Ver ícono de instalar en la barra de direcciones
3. O ir a Menú > "Instalar Salón Belleza HN"
4. La app se instalará como PWA de escritorio

---

## 📋 REQUERIMIENTOS TÉCNICOS

### Servidor
- PHP 8.2+ con extensiones: mysqli, gd, json
- MySQL 8.0+
- HTTPS recomendado (requerido para algunas funciones PWA)
- Headers CORS configurados si hay API externa

### Cliente (Móvil)
- Android 5.0+ o iOS 12+
- Navegador moderno (Chrome, Safari, Firefox)
- Conexión a internet para primera carga
- Al menos 50MB de espacio libre

### Navegadores Soportados
| Navegador | Versión Mínima | Funciones PWA |
|-----------|----------------|---------------|
| Chrome    | 67+            | ✅ Completo   |
| Safari    | 11.1+          | ✅ Parcial    |
| Firefox   | 58+            | ✅ Completo   |
| Edge      | 79+            | ✅ Completo   |

---

## 🔧 CONFIGURACIÓN ADICIONAL

### HTTPS (Recomendado)
Para producción, configurar HTTPS es esencial:

```apache
# .htaccess - Forzar HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Optimización de Imágenes
Las imágenes de productos deben ser:
- Formato: JPG o PNG
- Tamaño máximo: 2MB
- Dimensiones óptimas: 400x400px
- El sistema redimensiona automáticamente

### Performance
- Los assets CSS/JS están optimizados
- Service Worker cachea recursos estáticos
- Lazy loading para imágenes (implementar si hay muchas)
- Minificación recomendada para producción

---

## 🎯 FUNCIONES MÓVILES ESPECÍFICAS

### Punto de Venta (POS) Táctil
- Grid de productos/services grande para fácil selección
- Búsqueda en tiempo real
- Agregar al carrito con un tap
- Feedback visual y háptico (si soportado)

### Carrito Inteligente
- Persistencia en sessionStorage
- Cálculo automático de impuestos (ISV 15%/18%)
- Edición rápida de cantidades
- Vaciar carrito con confirmación

### Pago Rápido
- Modal bottom sheet en móviles
- Selección de método de pago con botones grandes
- Cálculo de cambio en tiempo real
- Integración con generación de PDF

### Navegación Intuitiva
- Barra inferior siempre visible
- Íconos claros con labels
- Badge de notificación en carrito
- Estado activo visible

### Offline First
- Service Worker cachea interfaz
- Mensajes de estado de conexión
- Operaciones en cola (por implementar)
- Sincronización al reconectar

---

## 📱 CAPTURA DE PANTALLA SUGERIDA

La app muestra:
1. **Login**: Formulario simple con logo
2. **Dashboard**: 4 tarjetas de stats + listas
3. **POS**: Grid de items + carrito flotante
4. **Carrito**: Lista de items con controles +/-
5. **Pago**: Modal con opciones grandes

---

## 🔐 SEGURIDAD EN MÓVILES

- Sesiones con timeout de 30 minutos
- Validación de rol en cada petición
- CSRF tokens en formularios
- Sanitización de todas las entradas
- HTTPS recomendado para proteger datos

---

## 📊 MÉTRICAS DE USO

Para monitorear adopción móvil:
- User Agent detection en analytics
- Eventos de instalación PWA
- Tiempo de sesión por dispositivo
- Funciones más usadas en móvil

---

## 🆘 SOPORTE Y SOLUCIÓN DE PROBLEMAS

### La app no se instala
- Verificar que sea HTTPS
- Chequear manifest.json válido
- Revisar console del navegador

### No funciona offline
- Service Worker requiere HTTPS o localhost
- Verificar rutas en sw.js
- Limpiar caché del navegador

### Problemas de visualización
- Forzar refresh (Ctrl+F5 o recargar)
- Limpiar caché de la app
- Verificar compatibilidad del navegador

---

## 📞 CONTACTO

Para soporte técnico o personalización:
- Email: soporte@salonbelleza.hn
- Documentación completa en README.md

---

**¡Tu salón de belleza ahora en la palma de tu mano! 📱✨**

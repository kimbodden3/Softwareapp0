/**
 * JavaScript principal para la aplicación móvil
 * Funcionalidades touch, navegación móvil y PWA
 */

// Registro del Service Worker para PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./sw.js')
            .then(registration => {
                console.log('ServiceWorker registrado:', registration.scope);
            })
            .catch(error => {
                console.log('Error al registrar ServiceWorker:', error);
            });
    });
}

// Variables globales
let cart = [];
let currentModule = 'dashboard';

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeMobileNav();
    initializeTouchInteractions();
    loadCartFromSession();
    updateCartDisplay();
});

/**
 * Navegación móvil
 */
function initializeMobileNav() {
    const navLinks = document.querySelectorAll('.mobile-nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover clase active de todos
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Agregar clase active al actual
            this.classList.add('active');
            
            // Navegar al módulo
            const module = this.getAttribute('data-module');
            navigateTo(module);
        });
    });
}

/**
 * Navegación entre módulos
 */
function navigateTo(module) {
    currentModule = module;
    
    // Actualizar URL sin recargar (para PWA)
    history.pushState({ module }, '', `?module=${module}`);
    
    // Cargar contenido del módulo
    loadModule(module);
}

/**
 * Cargar módulo vía AJAX
 */
function loadModule(module) {
    // Mostrar loading
    showLoading();
    
    fetch(`modules/${module}/index.php`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar módulo');
            return response.text();
        })
        .then(html => {
            document.getElementById('app-content').innerHTML = html;
            hideLoading();
            initializeModuleScripts(module);
        })
        .catch(error => {
            console.error('Error:', error);
            showError('No se pudo cargar el módulo. Verifique su conexión.');
            hideLoading();
        });
}

/**
 * Inicializar scripts específicos de cada módulo
 */
function initializeModuleScripts(module) {
    switch(module) {
        case 'pos':
            initializePOS();
            break;
        case 'cart':
            initializeCart();
            break;
        case 'inventory':
            initializeInventory();
            break;
        default:
            break;
    }
}

/**
 * Interacciones táctiles mejoradas
 */
function initializeTouchInteractions() {
    // Prevenir zoom double-tap en iOS
    let lastTouchEnd = 0;
    
    document.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
    
    // Efectos de presión en botones
    const buttons = document.querySelectorAll('.btn-touch, .pos-item');
    
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * Funciones del POS
 */
function initializePOS() {
    const posItems = document.querySelectorAll('.pos-item');
    
    posItems.forEach(item => {
        item.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemName = this.getAttribute('data-name');
            const itemPrice = parseFloat(this.getAttribute('data-price'));
            const itemType = this.getAttribute('data-type');
            const itemTax = parseFloat(this.getAttribute('data-tax'));
            
            addToCart(itemId, itemName, itemPrice, itemType, itemTax);
            
            // Feedback visual
            showNotification(`${itemName} agregado al carrito`);
        });
    });
    
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('pos-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            filterPOSItems(term);
        });
    }
}

/**
 * Filtrar items del POS
 */
function filterPOSItems(term) {
    const items = document.querySelectorAll('.pos-item');
    
    items.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const category = item.getAttribute('data-category').toLowerCase();
        
        if (name.includes(term) || category.includes(term)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Carrito de compras
 */
function addToCart(id, name, price, type, tax) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id,
            name,
            price,
            type,
            tax,
            quantity: 1
        });
    }
    
    saveCartToSession();
    updateCartDisplay();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    saveCartToSession();
    updateCartDisplay();
    
    if (currentModule === 'cart') {
        loadModule('cart'); // Recargar vista del carrito
    }
}

function updateCartItemQuantity(id, quantity) {
    const item = cart.find(item => item.id === id);
    
    if (item) {
        if (quantity <= 0) {
            removeFromCart(id);
        } else {
            item.quantity = quantity;
            saveCartToSession();
            updateCartDisplay();
        }
    }
}

function clearCart() {
    cart = [];
    saveCartToSession();
    updateCartDisplay();
}

/**
 * Guardar carrito en sessionStorage
 */
function saveCartToSession() {
    sessionStorage.setItem('pos_cart', JSON.stringify(cart));
}

/**
 * Cargar carrito desde sessionStorage
 */
function loadCartFromSession() {
    const savedCart = sessionStorage.getItem('pos_cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
}

/**
 * Actualizar display del carrito
 */
function updateCartDisplay() {
    const cartCount = document.querySelector('.cart-count');
    const cartTotal = document.querySelector('.cart-total');
    
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
        
        // Ocultar contador si está vacío
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }
    
    if (cartTotal) {
        const total = calculateTotal();
        cartTotal.textContent = formatCurrency(total);
    }
    
    // Actualizar badge en navegación
    updateNavBadge();
}

function updateNavBadge() {
    const navBadge = document.querySelector('.nav-cart-badge');
    if (navBadge) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        navBadge.textContent = totalItems;
        navBadge.style.display = totalItems > 0 ? 'block' : 'none';
    }
}

/**
 * Calcular total del carrito con impuestos
 */
function calculateTotal() {
    return cart.reduce((total, item) => {
        const subtotal = item.price * item.quantity;
        const taxAmount = subtotal * (item.tax / 100);
        return total + subtotal + taxAmount;
    }, 0);
}

/**
 * Inicializar vista del carrito
 */
function initializeCart() {
    renderCartItems();
    
    const checkoutBtn = document.getElementById('btn-checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', processCheckout);
    }
    
    const clearBtn = document.getElementById('btn-clear-cart');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (confirm('¿Está seguro de vaciar el carrito?')) {
                clearCart();
                loadModule('cart');
            }
        });
    }
}

/**
 * Renderizar items del carrito
 */
function renderCartItems() {
    const container = document.getElementById('cart-items');
    
    if (!container) return;
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p class="text-muted">El carrito está vacío</p>
                <button class="btn btn-primary btn-touch mt-3" onclick="navigateTo('pos')">
                    Ir al POS
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = cart.map(item => {
        const subtotal = item.price * item.quantity;
        const taxAmount = subtotal * (item.tax / 100);
        const total = subtotal + taxAmount;
        
        return `
            <div class="card-mobile mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-1">${item.name}</h4>
                        <small class="text-muted">
                            ${item.type === 'servicio' ? 'ISV 15%' : 'ISV 18%'} | 
                            L. ${formatNumber(item.price)} c/u
                        </small>
                    </div>
                    <div class="text-right">
                        <div class="d-flex align-items-center mb-2">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="updateCartItemQuantity('${item.id}', ${item.quantity - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="mx-3 font-weight-bold">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="updateCartItemQuantity('${item.id}', ${item.quantity + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="font-weight-bold text-primary">
                            L. ${formatNumber(total)}
                        </div>
                        <button class="btn btn-sm btn-danger mt-1" 
                                onclick="removeFromCart('${item.id}')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Renderizar totales
    renderCartTotals();
}

/**
 * Renderizar totales del carrito
 */
function renderCartTotals() {
    const totalsContainer = document.getElementById('cart-totals');
    
    if (!totalsContainer) return;
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax15 = cart
        .filter(item => item.type === 'servicio')
        .reduce((sum, item) => sum + (item.price * item.quantity * 0.15), 0);
    const tax18 = cart
        .filter(item => item.type === 'producto')
        .reduce((sum, item) => sum + (item.price * item.quantity * 0.18), 0);
    const total = subtotal + tax15 + tax18;
    
    totalsContainer.innerHTML = `
        <div class="card-mobile">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span class="font-weight-bold">L. ${formatNumber(subtotal)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>ISV 15% (Servicios):</span>
                <span>L. ${formatNumber(tax15)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>ISV 18% (Productos):</span>
                <span>L. ${formatNumber(tax18)}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <span class="font-weight-bold">TOTAL:</span>
                <span class="font-weight-bold text-primary" style="font-size: 1.5rem;">
                    L. ${formatNumber(total)}
                </span>
            </div>
            <button id="btn-checkout" class="btn btn-success btn-touch w-100">
                <i class="fas fa-cash-register"></i> Procesar Pago
            </button>
        </div>
    `;
}

/**
 * Procesar checkout
 */
function processCheckout() {
    if (cart.length === 0) {
        showError('El carrito está vacío');
        return;
    }
    
    // Abrir modal de pago
    showModal('payment');
}

/**
 * Inicializar inventario
 */
function initializeInventory() {
    // Búsqueda de productos
    const searchInput = document.getElementById('inventory-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            filterInventory(term);
        });
    }
    
    // Filtros por categoría
    const categoryFilters = document.querySelectorAll('.inventory-filter');
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            filterByCategory(category);
        });
    });
}

/**
 * Filtrar inventario
 */
function filterInventory(term) {
    const items = document.querySelectorAll('.inventory-item');
    
    items.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const code = item.getAttribute('data-code').toLowerCase();
        
        if (name.includes(term) || code.includes(term)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Filtrar por categoría
 */
function filterByCategory(category) {
    const items = document.querySelectorAll('.inventory-item');
    
    items.forEach(item => {
        const itemCategory = item.getAttribute('data-category');
        
        if (category === 'all' || itemCategory === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Utilidades
 */

/**
 * Formatear moneda hondureña
 */
function formatCurrency(amount) {
    return 'L. ' + formatNumber(amount);
}

/**
 * Formatear número con separadores de miles
 */
function formatNumber(num) {
    return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Mostrar loading
 */
function showLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) {
        loader.style.display = 'flex';
    }
}

/**
 * Ocultar loading
 */
function hideLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) {
        loader.style.display = 'none';
    }
}

/**
 * Mostrar notificación toast
 */
function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Mostrar error
 */
function showError(message) {
    showNotification(message, 'error');
}

/**
 * Modal genérico
 */
function showModal(type) {
    const modal = document.getElementById(`modal-${type}`);
    if (modal) {
        modal.classList.add('active');
    }
}

function hideModal(type) {
    const modal = document.getElementById(`modal-${type}`);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Detectar conexión offline
 */
window.addEventListener('offline', function() {
    showNotification('Sin conexión. Algunas funciones pueden no estar disponibles.', 'warning');
});

window.addEventListener('online', function() {
    showNotification('Conexión restablecida', 'success');
});

/**
 * Manejar botón atrás en Android
 */
if (navigator.userAgent.match(/Android/i)) {
    document.addEventListener('backbutton', function(e) {
        e.preventDefault();
        
        // Si hay modales abiertos, cerrarlos
        const activeModals = document.querySelectorAll('.modal-touch.active');
        if (activeModals.length > 0) {
            activeModals.forEach(modal => modal.classList.remove('active'));
            return false;
        }
        
        // Si no estamos en dashboard, ir al dashboard
        if (currentModule !== 'dashboard') {
            navigateTo('dashboard');
            return false;
        }
        
        // Confirmar salida
        if (confirm('¿Desea salir de la aplicación?')) {
            navigator.app.exitApp();
        }
        
        return false;
    }, false);
}

console.log('App móvil inicializada correctamente');

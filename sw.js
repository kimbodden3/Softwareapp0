/**
 * Service Worker para la aplicación móvil
 * Permite cachear recursos y funcionamiento offline parcial
 */

const CACHE_NAME = 'salon-belleza-v1';
const urlsToCache = [
    './',
    './index.php',
    './assets/css/styles.css',
    './assets/js/app.js',
    './manifest.json'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Archivos cacheados');
                return cache.addAll(urlsToCache);
            })
    );
});

// Activación y limpieza de cachés antiguos
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Eliminando caché antiguo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Interceptación de peticiones - Estrategia: Cache First, luego Network
self.addEventListener('fetch', event => {
    // Solo manejar peticiones GET
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Si está en caché, devolverlo
                if (response) {
                    return response;
                }

                // Si no, hacer petición a red
                return fetch(event.request).then(response => {
                    // Verificar si es respuesta válida
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clonar la respuesta para guardar en caché
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                });
            })
            .catch(() => {
                // Fallback para cuando no hay conexión
                if (event.request.url.includes('.html') || event.request.url.includes('index.php')) {
                    return caches.match('./index.php');
                }
            })
    );
});

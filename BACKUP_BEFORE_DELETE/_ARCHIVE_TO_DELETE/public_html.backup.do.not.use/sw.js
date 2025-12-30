/**
 * Service Worker pour GeekBoard Dashboard
 * Optimise les performances avec mise en cache intelligente
 */

const CACHE_NAME = 'geekboard-v1.0.0';
const CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 24 heures

// Ressources critiques à mettre en cache immédiatement
const CRITICAL_RESOURCES = [
    'assets/css/dashboard-optimized.css',
    'assets/js/dashboard-optimized.js',
    'assets/images/logo.png',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// Ressources à mettre en cache lors de la première utilisation
const CACHE_ON_DEMAND = [
    'index.php',
    'assets/images/',
    'api/'
];

// Installation du Service Worker
self.addEventListener('install', event => {
    console.log('Service Worker: Installation');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Mise en cache des ressources critiques');
                return cache.addAll(CRITICAL_RESOURCES);
            })
            .then(() => {
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker: Erreur installation', error);
            })
    );
});

// Activation du Service Worker
self.addEventListener('activate', event => {
    console.log('Service Worker: Activation');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Service Worker: Suppression ancien cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Interception des requêtes
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Ignorer les requêtes non-GET et les requêtes externes non critiques
    if (request.method !== 'GET' || 
        (url.origin !== self.location.origin && !CRITICAL_RESOURCES.includes(request.url))) {
        return;
    }
    
    // Stratégie de cache selon le type de ressource
    if (isCriticalResource(request.url)) {
        // Cache First pour les ressources critiques
        event.respondWith(cacheFirst(request));
    } else if (isAPIRequest(request.url)) {
        // Network First pour les API avec fallback cache
        event.respondWith(networkFirst(request));
    } else if (isStaticResource(request.url)) {
        // Stale While Revalidate pour les ressources statiques
        event.respondWith(staleWhileRevalidate(request));
    } else {
        // Network First par défaut
        event.respondWith(networkFirst(request));
    }
});

// Stratégie Cache First
async function cacheFirst(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse && !isExpired(cachedResponse)) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
        
    } catch (error) {
        console.error('Cache First error:', error);
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Offline', { status: 503 });
    }
}

// Stratégie Network First
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
        
    } catch (error) {
        console.error('Network First error:', error);
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Offline', { status: 503 });
    }
}

// Stratégie Stale While Revalidate
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    // Réponse en arrière-plan pour mettre à jour le cache
    const networkResponsePromise = fetch(request)
        .then(networkResponse => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch(error => {
            console.error('Stale While Revalidate error:', error);
        });
    
    // Retourner immédiatement la réponse en cache si disponible
    return cachedResponse || networkResponsePromise;
}

// Utilitaires
function isCriticalResource(url) {
    return CRITICAL_RESOURCES.some(resource => url.includes(resource));
}

function isAPIRequest(url) {
    return url.includes('/api/') || url.includes('.php');
}

function isStaticResource(url) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/.test(url);
}

function isExpired(response) {
    const cacheDate = response.headers.get('date');
    if (!cacheDate) return false;
    
    const cacheTime = new Date(cacheDate).getTime();
    return (Date.now() - cacheTime) > CACHE_EXPIRY;
}

// Nettoyage périodique du cache
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'CLEAN_CACHE') {
        cleanOldCache();
    }
});

async function cleanOldCache() {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    
    for (const request of requests) {
        const response = await cache.match(request);
        if (isExpired(response)) {
            await cache.delete(request);
            console.log('Service Worker: Cache expiré supprimé', request.url);
        }
    }
}

// Nettoyage automatique toutes les heures
setInterval(cleanOldCache, 60 * 60 * 1000);

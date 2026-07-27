const CACHE_NAME = 'visita-kiosk-cache-v2';
const ASSETS_TO_CACHE = [
    '/js/face-api.min.js',
    '/models/face_landmark_68_model-shard1',
    '/models/face_landmark_68_model-weights_manifest.json',
    '/models/face_recognition_model-shard1',
    '/models/face_recognition_model-shard2',
    '/models/face_recognition_model-weights_manifest.json',
    '/models/tiny_face_detector_model-shard1',
    '/models/tiny_face_detector_model-weights_manifest.json',
    '/favicon.ico'
];

// Install Event: Cache all critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Pre-caching models and face-api');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: Cache-First strategy for static assets, network fallback
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip POST requests, Livewire, admin, and polling endpoints
    if (
        event.request.method !== 'GET' || 
        url.pathname.startsWith('/livewire') || 
        url.pathname.startsWith('/appointments') ||
        url.pathname.startsWith('/kiosk/face') ||
        url.pathname.startsWith('/admin')
    ) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request).then((networkResponse) => {
                // Dynamically cache models, js, css, and images
                if (networkResponse.status === 200 && (
                    url.pathname.startsWith('/models/') || 
                    url.pathname.startsWith('/js/') || 
                    url.pathname.startsWith('/css/') ||
                    url.pathname.startsWith('/fonts/') ||
                    url.pathname.startsWith('/assets/')
                )) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                if (event.request.mode === 'navigate') {
                    return caches.match('/');
                }
            });
        })
    );
});

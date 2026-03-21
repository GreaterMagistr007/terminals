const CACHE_NAME = 'terminals-v1';
const API_CACHE_NAME = 'terminals-api-v1';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
];

// Установка: кешируем базовые ресурсы
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Активация: удаляем старые кеши
self.addEventListener('activate', (event) => {
    const validCaches = [CACHE_NAME, API_CACHE_NAME];
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => !validCaches.includes(key))
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// Стратегии кеширования по типу запроса
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Игнорируем не-HTTP запросы (chrome-extension://, etc.)
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // API-запросы
    if (url.pathname.startsWith('/api/')) {
        // POST/PUT/DELETE -- Network Only, при ошибке -- 503
        if (request.method !== 'GET') {
            event.respondWith(
                fetch(request).catch(() =>
                    new Response(
                        JSON.stringify({ error: 'offline' }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json' },
                        }
                    )
                )
            );
            return;
        }

        // GET -- Network First с кешем
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Кешируем успешные ответы
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(API_CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() =>
                    // При ошибке сети -- возвращаем из кеша или 503
                    caches.match(request).then((cached) => {
                        if (cached) return cached;
                        return new Response(
                            JSON.stringify({ error: 'offline' }),
                            {
                                status: 503,
                                headers: { 'Content-Type': 'application/json' },
                            }
                        );
                    })
                )
        );
        return;
    }

    // Навигация (HTML): Network First, fallback на кеш
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() => caches.match('/'))
        );
        return;
    }

    // Статика с хешем (Vite build assets): Cache First
    if (url.pathname.startsWith('/build/assets/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Остальная статика: Network First
    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok && request.method === 'GET') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

const CACHE_NAME = 'terminals-v2';
const API_CACHE_NAME = 'terminals-api-v1';

// Прекеширование: загрузить HTML-оболочку и все её ассеты
async function precacheAppShell() {
    const cache = await caches.open(CACHE_NAME);

    // Загрузить HTML-оболочку
    const response = await fetch('/');
    await cache.put('/', response.clone());

    // Извлечь URL ассетов из HTML
    const html = await response.text();
    const urls = [];
    const regex = /(?:src|href)="(\/build\/assets\/[^"]+)"/g;
    let match;
    while ((match = regex.exec(html)) !== null) {
        urls.push(match[1]);
    }

    // Также закешировать manifest и иконки
    urls.push('/manifest.json', '/favicon.ico');

    await Promise.all(urls.map((url) => cache.add(url).catch(() => {})));
}

// Установка: прекеширование app shell с ассетами
self.addEventListener('install', (event) => {
    event.waitUntil(precacheAppShell());
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

// Обновление кеша ассетов по запросу из приложения (fallback)
self.addEventListener('message', (event) => {
    if (event.data?.type === 'CACHE_ASSETS' && Array.isArray(event.data.urls)) {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) =>
                Promise.all(
                    event.data.urls.map((url) =>
                        cache.match(url).then((cached) => {
                            if (!cached) return cache.add(url).catch(() => {});
                        })
                    )
                )
            )
        );
    }
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
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(API_CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() =>
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

    // Навигация (HTML): Network First, fallback на кешированную SPA-оболочку
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put('/', clone));
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

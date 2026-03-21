const CACHE_NAME = 'terminals-v3';
const API_CACHE_NAME = 'terminals-api-v1';

/**
 * Прекеширование: HTML-оболочка + все Vite-ассеты из манифеста.
 * Манифест генерируется Vite при сборке и содержит точные пути всех JS/CSS.
 */
async function precacheAppShell() {
    const cache = await caches.open(CACHE_NAME);

    // HTML-оболочка SPA
    const htmlResponse = await fetch('/');
    await cache.put('/', htmlResponse);

    // Все Vite-ассеты из build-манифеста
    try {
        const manifestResponse = await fetch('/build/manifest.json');
        const manifest = await manifestResponse.json();
        const urls = new Set();

        for (const entry of Object.values(manifest)) {
            if (entry.file) urls.add('/build/' + entry.file);
            if (entry.css) entry.css.forEach((f) => urls.add('/build/' + f));
        }

        // Статические ресурсы
        urls.add('/manifest.json');
        urls.add('/favicon.ico');

        await Promise.all(
            [...urls].map((url) => cache.add(url).catch(() => {}))
        );
    } catch {
        // Манифест недоступен — кешируем только HTML
    }
}

// Установка: прекешируем app shell + все ассеты
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

// Стратегии кеширования по типу запроса
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (!url.protocol.startsWith('http')) {
        return;
    }

    // API-запросы
    if (url.pathname.startsWith('/api/')) {
        if (request.method !== 'GET') {
            event.respondWith(
                fetch(request).catch(() =>
                    new Response(
                        JSON.stringify({ error: 'offline' }),
                        { status: 503, headers: { 'Content-Type': 'application/json' } }
                    )
                )
            );
            return;
        }

        // GET — Network First + кеш
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
                            { status: 503, headers: { 'Content-Type': 'application/json' } }
                        );
                    })
                )
        );
        return;
    }

    // Навигация (HTML): Network First, fallback на SPA-оболочку
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

    // Vite build assets: Cache First
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
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

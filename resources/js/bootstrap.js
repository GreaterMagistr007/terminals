// Регистрация Service Worker для PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.error('SW registration failed:', error);
        });
    });
}

// Полная блокировка системной кнопки "Назад".
// Перехватываем popstate ДО Vue Router (bootstrap загружается первым).
// stopImmediatePropagation не даёт событию дойти до обработчика Vue Router.
window.addEventListener('popstate', (e) => {
    e.stopImmediatePropagation();
    history.pushState(null, '', location.href);
}, true);
history.pushState(null, '', location.href);

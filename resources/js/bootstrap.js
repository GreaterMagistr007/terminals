// Регистрация Service Worker для PWA.
// updateViaCache:'none' заставляет браузер каждый раз сверять /sw.js с сервером,
// не беря его из HTTP-кеша. Без этого новый BUILD_ID в шаблоне SW может
// неделями не долетать до устройства.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                updateViaCache: 'none',
            });

            // Принудительная проверка обновлений при возврате фокуса на вкладку —
            // операторы обычно держат PWA открытым часами, так мы быстрее подхватим
            // свежую сборку после деплоя.
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    registration.update().catch(() => {});
                }
            });
        } catch (error) {
            console.error('SW registration failed:', error);
        }
    });
}

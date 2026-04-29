// Регистрация Service Worker для PWA.
// updateViaCache:'none' заставляет браузер каждый раз сверять /sw.js с сервером,
// не беря его из HTTP-кеша. Без этого новый BUILD_ID в шаблоне SW может
// неделями не долетать до устройства.

// Как часто принудительно опрашиваем сервер на наличие нового SW.
// 1 час -- ночные деплои не делаются, дневные обновления подхватятся максимум за час.
const SW_UPDATE_INTERVAL_MS = 60 * 60 * 1000;

// Ночью проверки обновлений не нужны: деплои в это время не происходят,
// а лишние запросы только тратят батарею устройств.
// Ночь определяем по иркутскому времени (часовой пояс операторов).
const NIGHT_START_HOUR = 23; // 23:00 включительно
const NIGHT_END_HOUR = 7;    // до 07:00 (не включая)
const OPERATORS_TZ = 'Asia/Irkutsk';

function isNightTime() {
    try {
        const hour = parseInt(
            new Intl.DateTimeFormat('en-US', {
                timeZone: OPERATORS_TZ,
                hour: '2-digit',
                hour12: false,
            }).format(new Date()),
            10,
        );
        return hour >= NIGHT_START_HOUR || hour < NIGHT_END_HOUR;
    } catch {
        return false;
    }
}

function tryUpdate(registration) {
    if (isNightTime()) return;
    registration.update().catch(() => {});
}

// Флаг "ждём безопасного момента для перезагрузки". Поднимается, когда:
//   - controllerchange случился, пока оператор находится в форме обслуживания,
//   - либо в момент, когда устройство офлайн (reload без сети может оставить
//     пустой UI, если ни /sw.js не вернётся, ни кеш).
// Применяется при первом уходе с формы (см. router/index.js::afterEach)
// или при возврате online (см. window.addEventListener('online') ниже).
let pendingReload = false;
let onlineListenerAttached = false;

/**
 * Безопасный reload: только если есть сеть. Иначе откладываем до события 'online'.
 * Это страховка от того, чтобы случайно не уйти на /login: SPA-инициализация после
 * reload без сети может не получить /auth/me, но fetchUser падает на сетевой ошибке
 * и берёт пользователя из localStorage -- разлогинить не должно. Тем не менее
 * перестраховываемся: офлайн-reload смысла не имеет, новые ассеты всё равно не подгрузятся.
 */
function safeReload(reason) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        pendingReload = true;
        attachOnlineListenerOnce();
        return;
    }
    window.location.reload();
}

function attachOnlineListenerOnce() {
    if (onlineListenerAttached) return;
    onlineListenerAttached = true;
    window.addEventListener('online', () => {
        if (!pendingReload) return;
        // Если оператор всё ещё в форме обслуживания -- не дёргаем,
        // дождёмся его выхода через router.afterEach.
        if (window.location.pathname.startsWith('/service/')) return;
        pendingReload = false;
        window.location.reload();
    });
}

/**
 * Перезагружает страницу, если ранее был отложенный reload и текущий путь безопасен.
 * Вызывается из router.afterEach при смене маршрута.
 */
export function applyPendingReloadIfSafe(path) {
    if (!pendingReload) return;
    if (typeof path === 'string' && path.startsWith('/service/')) return;
    pendingReload = false;
    safeReload('router-afterEach');
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                updateViaCache: 'none',
            });

            // Периодическая проверка обновлений: гарантия для PWA, которые
            // постоянно открыты (visibilitychange не срабатывает).
            setInterval(() => tryUpdate(registration), SW_UPDATE_INTERVAL_MS);

            // Принудительная проверка обновлений при возврате фокуса на вкладку.
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    tryUpdate(registration);
                }
            });

            // При появлении сети после офлайна -- сразу проверяем обновление.
            window.addEventListener('online', () => tryUpdate(registration));
        } catch (error) {
            console.error('SW registration failed:', error);
        }
    });

    // Когда новый SW вступает в управление страницей -- перезагружаем,
    // чтобы JS-модули в памяти подменились на свежий бандл (иначе пользователь
    // продолжит работать на старой версии до ручного reload).
    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) return;

        // На странице активного обслуживания не перезагружаем сами,
        // чтобы не прервать оператора посреди ввода. Поднимаем флаг --
        // reload произойдёт автоматически при первом уходе с формы.
        const onServiceForm = window.location.pathname.startsWith('/service/');

        if (onServiceForm) {
            pendingReload = true;
            attachOnlineListenerOnce();
            document.dispatchEvent(new CustomEvent('app:toast', {
                detail: {
                    message: 'Доступно обновление. Применится после завершения визита.',
                    type: 'success',
                },
            }));
            return;
        }

        reloading = true;
        safeReload('controllerchange');
    });
}

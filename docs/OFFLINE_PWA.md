# Offline-очередь визитов и Service Worker

Операторы работают в торговых точках с плохим мобильным интернетом. Приложение должно быть устойчиво к «висящим» запросам и полному отсутствию сети, а после деплоя — гарантированно подхватывать новый бандл без действий оператора.

## Offline-очередь визитов

### Сохранение нового визита

`resources/js/pages/Service.vue::saveVisit()`:

```
buildVisitData() → offlineQueueStore.enqueue() → deleteDraft() → router.push('/')
                                               └→ в фоне: offlineQueueStore.syncAll()
```

1. Визит всегда попадает в IndexedDB **сначала**.
2. После этого — `router.push('/')`: UI возвращается на главную мгновенно.
3. Если есть сеть — фоновый `syncAll()` без `await`.
4. Если сети нет — визит остаётся в очереди, в Home показывается баннер «Ожидает отправки».

### Редактирование визита

Синхронный POST с `_method=PUT`. Offline-очередь не поддерживает редактирование. При отсутствии сети — тост «Редактирование недоступно без интернета».

### Компоненты очереди

| Файл | Ответственность |
|------|-----------------|
| `resources/js/services/offlineDb.js` | IndexedDB: `savePendingVisit`, `getPendingVisits`, `deletePendingVisit`, `updateSyncStatus`, **`resetAllSyncAttempts`**, `saveDraft`/`getDraft`/`deleteDraft`/`getAllDrafts`. |
| `resources/js/stores/offlineQueue.js` (Pinia) | `enqueue`, `syncAll`, **`retryAll`**, `loadCount`, `pendingCount`, `syncing`, **`lastSyncError`**. |
| `resources/js/layouts/AppLayout.vue` | `backgroundFetch` раз в минуту + listener `online` → `syncAll`. `showToast(message, type='success'\|'error')` + слушатель события `'app:toast'` через document. |
| `resources/js/pages/Home.vue` | Кликабельный баннер: `pendingCount > 0` → `retryAll()` + тост «переотправляются». При ошибке — красный тост и подпись с `lastSyncError.message`. |

### Идемпотентность отправки

Сервер может успешно создать визит, но 201-ответ потеряться в дороге (плохая сеть, мобильный браузер ушёл в background, таймаут прокси). Без защиты следующий тик `syncAll` отправит тот же визит ещё раз → второй `ServiceVisit` в БД, второе уведомление в Telegram.

Защита — idempotency-ключ. У каждой записи в IndexedDB есть `id: crypto.randomUUID()` (`offlineDb.js`). `buildFormData` кладёт его в `client_uuid` поле. Бэк (`ServiceVisitController::store`):

1. Если в БД уже есть `service_visit` с этим `client_uuid` — возвращает существующий (`200`), без создания, без фото, без ротации, без Telegram. Клиент удаляет запись из IndexedDB как при обычном успехе.
2. Иначе создаёт визит с этим `client_uuid` внутри `DB::transaction`. Если параллельный ретрай выиграл гонку — `INSERT` падает на unique-индексе `service_visits.client_uuid`, ловим `UniqueConstraintViolationException`, возвращаем уже созданный визит.

Поле `client_uuid` (`CHAR(36) NULLABLE UNIQUE`, миграция `2026_05_01_000000_add_client_uuid_to_service_visits`) — nullable: сторонние клиенты без UUID получают старое поведение, но без идемпотентности. Редактирование (PUT) не передаёт `client_uuid` и идёт мимо этого механизма.

### Manual retry

`MAX_SYNC_ATTEMPTS = 5`: после 5 неудачных попыток визит молча пропускается в `syncAll`. Оператор кликает по баннеру → `retryAll()` = `resetAllSyncAttempts()` (всем pending записям обнуляется `syncAttempts`) + `syncAll()`.

Это решает проблему «застрявшего» визита, который из-за серверных ошибок (422 / 500) накопил 5 попыток и больше никогда не отправится автоматически.

### Логирование ошибок sync на сервер

При любой серверной ошибке в `syncAll` (есть `error.response`) store шлёт `apiClient.post('/client-errors', ...)` fire-and-forget с:

- `source: 'offline-sync'` (или `'sync-retry-419'` если упала повторная попытка после CSRF refresh)
- `message`: читаемое описание (`[status] message` или валидационная ошибка)
- `context`: `visit_id`, `terminal_id`, `terminal_name`, `sync_attempts`, `status`, `response_data` (обрезанное до 2000 символов), `error_name`, `error_code`, `error_message`
- `url`: текущий URL

См. `CLIENT_ERROR_LOGGING.md`. Сетевые ошибки без `response` не логируются — серверу-то всё равно не дойдут.

### Критерий успеха

- «Сохранить» → мгновенный переход на главную (< 100 мс) независимо от сети.
- Потеря связи в момент сохранения не приводит к потере визита.
- Повторный вход в приложение не дублирует визиты.
- Застрявший визит можно переотправить кликом по баннеру.
- Причина залипания доступна админу в `/admin/client-errors` без физического доступа к телефону.

## Service Worker

### Источник и сборка

```
resources/sw.template.js   ──(scripts/stamp-sw.cjs)──▶   public/sw.js  (в .gitignore)
```

`stamp-sw.cjs` подставляет `BUILD_ID = Date.now()` при `npm run build` и `npm run dev`.

### Именование кеша

```js
const BUILD_ID = '__BUILD_ID__';
const CACHE_NAME = 'terminals-' + BUILD_ID;
const API_CACHE_NAME = 'terminals-api-' + BUILD_ID;
```

При каждой сборке имена кешей меняются. `activate` удаляет всё, что не входит в текущий список.

### Стратегии

| Путь | Стратегия | Почему |
|------|-----------|--------|
| `/api/*` GET | Network First → cache | Свежее лучше, кеш как fallback. |
| `/api/*` не-GET | Network only | Мутации не кешируем, при offline → 503 JSON. |
| `request.mode === 'navigate'` | Network First → `/` из кеша | SPA-оболочка. |
| `/build/manifest.json`, `/build/.vite/manifest.json` | Network First | Cache First запирает старый манифест со ссылками на удалённые чанки. |
| `/build/*` | Cache First | Имена хэшированные — cache-busting встроен. |
| Остальное | Network First → cache | Фото, `/manifest.json` и т.п. |

## PWA auto-update

### Зачем нужна отдельная стратегия

SW обновляет сетевые ассеты, но **не может перезагрузить уже выполняющуюся страницу**. JS-модули в памяти Vue-приложения остаются старыми после `controllerchange`. Без явного `location.reload()` оператор продолжает работать на старом коде.

Пример: баг с непрерывным голосовым вводом в форме обслуживания возвращался у тех, кто открыл PWA до деплоя — SW обновлялся, но активная страница продолжала использовать старый `Service.vue` из памяти.

### Слои защиты

`resources/js/bootstrap.js`:

```js
const SW_UPDATE_INTERVAL_MS = 60 * 60 * 1000;  // 1 час
const NIGHT_START_HOUR = 23;                    // 23:00 Asia/Irkutsk
const NIGHT_END_HOUR = 7;                       // до 07:00

function tryUpdate(registration) {
    if (isNightTime()) return;  // ночные деплои не делаются
    registration.update().catch(() => {});
}
```

Триггеры проверки обновлений (все идут через `tryUpdate`, ночью пропускаются):

| Триггер | Когда срабатывает |
|---------|-------------------|
| `setInterval(tryUpdate, 60min)` | главное — гарантия для постоянно открытых PWA |
| `visibilitychange` → `visible` | при возврате фокуса на вкладку |
| `online` | при появлении сети после офлайна |

### controllerchange + safeReload

```js
navigator.serviceWorker.addEventListener('controllerchange', () => {
    const onServiceForm = window.location.pathname.startsWith('/service/');

    if (onServiceForm) {
        pendingReload = true;
        showToast('Доступно обновление. Применится после завершения визита.', 'success');
        return;
    }

    safeReload();  // location.reload, но только при navigator.onLine
});
```

`safeReload()` — обёртка над `location.reload()`:
- Если `navigator.onLine === false` → ставит `pendingReload = true` и слушатель `'online'`.
- Иначе → `location.reload()`.

Это страховка от ситуации, когда SW активирован в момент сетевого сбоя — лучше дождаться сети.

### Отложенный reload в форме обслуживания

`resources/js/router/index.js`:

```js
import { applyPendingReloadIfSafe } from '@/bootstrap';

router.afterEach((to) => {
    applyPendingReloadIfSafe(to.fullPath);
});
```

`applyPendingReloadIfSafe(path)`:
- Если `pendingReload` поднят и `path` НЕ `/service/*` → `safeReload()`.

То есть оператор, которого `controllerchange` поймал в форме обслуживания:
1. Видит синий тост «Доступно обновление...».
2. Спокойно дозаполняет визит.
3. После сохранения попадает на главную → срабатывает `router.afterEach` → reload подхватывает свежий бандл.

### Полная таблица сценариев

| Сценарий оператора | Когда применится обновление |
|---------------------|------------------------------|
| PWA свёрнута, потом развёрнута | моментально (visibilitychange) |
| PWA постоянно на экране | максимум через 1 час (interval) |
| Был офлайн, появилась сеть | моментально (online) |
| controllerchange на главной/админке | моментально (auto-reload) |
| controllerchange в форме `/service/*` | при первом выходе из формы (router.afterEach) |
| Lazy-чанк не загрузился (старый манифест в памяти) | моментально (router.onError → reload) |
| Любой триггер ночью (23:00–07:00 Иркутск) | пропускается до 07:00 |

## Auth-store — устойчивость к reload

`resources/js/stores/auth.js`:

```js
async fetchUser() {
    try {
        const { data } = await apiClient.get('/auth/me');
        this.user = data.user;
        this._saveToStorage();
    } catch {
        if (!this.user) {
            this._loadFromStorage();  // restore from localStorage
        }
    } finally {
        this.loaded = true;
    }
}
```

При reload в плохой сети `fetchUser` падает на сетевой ошибке → пользователь восстанавливается из `localStorage` `auth-user-cache`. `isAuthenticated` остаётся `true`, на `/login` не выкидывает.

См. также `DEPLOY.md` раздел «Сессии и не-разлогинивание»: `SESSION_LIFETIME=2628000` (5 лет idle).

## Если снова сломается

- DevTools (через `chrome://inspect/#devices` для PWA на Android) → Application → Service Workers: смотреть `/sw.js`, `Status`, нажать `Update`.
- Application → Cache Storage: должны быть только `terminals-<BUILD_ID>` и `terminals-api-<BUILD_ID>` с одним и тем же свежим `BUILD_ID`. Всё остальное — зомби-кеши, удалить.
- Application → IndexedDB → `terminals-offline` → `pendingVisits`: смотреть записи с `syncStatus: 'failed'` и `syncError`.
- В админке `/admin/client-errors` — фильтр по `source = 'offline-sync'`, последние ошибки с context (response_data сервера и т.п.).

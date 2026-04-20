# Offline-очередь визитов и Service Worker

Операторы работают в торговых точках с плохим мобильным интернетом. Приложение должно быть устойчиво к «висящим» запросам и полному отсутствию сети.

## Offline-очередь визитов

### Сохранение нового визита

`resources/js/pages/Service.vue::saveVisit()`:

```
buildVisitData() → offlineQueueStore.enqueue() → deleteDraft() → router.push('/')
                                               └→ в фоне: offlineQueueStore.syncAll()
```

1. Визит всегда попадает в IndexedDB **сначала**.
2. Только после этого — `router.push('/')`: UI возвращается на главную мгновенно, без ожидания сети.
3. Если есть `navigator.onLine`, запускается фоновый `syncAll()` без `await`. Результат сообщается всем через `CustomEvent('vendista:updated')`.
4. Если сети нет или она обрубится — визит остаётся в очереди, `pendingCount` растёт, в Home.vue появляется баннер «Ожидает отправки».

### Редактирование визита

Редактирование — синхронный POST с `_method=PUT`. Offline-очередь на это не рассчитана (её `buildFormData` собирает только create-запрос). При отсутствии сети во время редактирования показывается тост «Редактирование недоступно без интернета», форма остаётся на экране.

Это сознательный компромисс: редактирование — редкий путь через «Изменить последний визит», операторы почти всегда создают новые визиты.

### Компоненты очереди

| Файл | Ответственность |
|------|-----------------|
| `resources/js/services/offlineDb.js` | Обёртка IndexedDB: `savePendingVisit`, `getPendingVisits`, `deletePendingVisit`, `updateSyncStatus`, драфты. |
| `resources/js/stores/offlineQueue.js` (Pinia) | `enqueue()`, `syncAll()`, `loadCount()`, `pendingCount`, `syncing`. `syncAll` перебирает ожидающие визиты, при сетевой ошибке прерывается (ждёт `online`). |
| `resources/js/layouts/AppLayout.vue` | Сборка очереди вместе с остальной фоновой активностью: `backgroundFetch` раз в минуту + listener `window.addEventListener('online', onOnline)`. При успехе отправки — тост и `CustomEvent('vendista:updated')`. |
| `resources/js/pages/Home.vue` | Показывает баннер `offlineQueueStore.pendingCount > 0`. |

### Критерий успеха

- «Сохранить» → мгновенный переход на главную (< 100 мс) независимо от состояния сети.
- Потеря связи в момент сохранения не приводит к потере визита.
- Повторный вход в приложение не дублирует визиты.

## Service Worker

### Источник и сборка

```
resources/sw.template.js   ──(scripts/stamp-sw.cjs)──▶   public/sw.js  (в .gitignore)
```

Шаблон коммитится, сгенерированный `public/sw.js` — нет. `stamp-sw.cjs` подставляет `BUILD_ID = Date.now()` при каждом `npm run build` и `npm run dev`.

### Именование кеша

```js
const BUILD_ID = '__BUILD_ID__';
const CACHE_NAME = 'terminals-' + BUILD_ID;
const API_CACHE_NAME = 'terminals-api-' + BUILD_ID;
```

При каждой сборке имена кешей меняются. `activate` удаляет всё, что не входит в текущий список:

```js
caches.keys().then(keys =>
    Promise.all(keys.filter(k => !validCaches.includes(k)).map(k => caches.delete(k)))
);
```

### Стратегии

| Путь | Стратегия | Почему |
|------|-----------|--------|
| `/api/*` GET | Network First → cache | Обычные данные: свежее лучше, кеш как fallback. |
| `/api/*` не-GET | Network only | Мутации не кешируем, при offline → 503 JSON. |
| `request.mode === 'navigate'` | Network First → `/` из кеша | SPA-оболочка. |
| `/build/manifest.json`, `/build/.vite/manifest.json` | Network First | **Важно.** Cache First здесь запирает старый манифест со ссылками на удалённые чанки → клиент получает «перемешанную» версию. |
| `/build/*` | Cache First | Имена хэшированные — cache-busting встроен. |
| Остальное | Network First → cache | Фото, `/manifest.json`, `/favicon.ico` и т.п. |

### Проверка обновлений

`resources/js/bootstrap.js`:

```js
const registration = await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        registration.update().catch(() => {});
    }
});
```

- `updateViaCache: 'none'` — браузер не кеширует сам `/sw.js`, каждый раз свежий запрос к серверу.
- `registration.update()` при возврате фокуса — PWA, открытое часами, сразу подхватит свежую сборку после деплоя.

### Что это фиксит

До этого `CACHE_NAME` был константой `'terminals-v3'`. После деплоя:
- Байты `/sw.js` не менялись → браузер не переустанавливал SW.
- `activate` видел своё имя в списке → не чистил кеш.
- `/build/manifest.json` сидел в Cache First → клиент получал ссылки на старые чанки, которых уже нет на сервере или которые ведут к старому `index.html` из кеша.

В итоге операторы на разных устройствах видели разные версии фронта, обновление требовало ручной чистки кеша в настройках браузера.

## Если снова сломается

- В консоли DevTools → Application → Service Workers: смотреть `/sw.js`, `Status`, нажать `Update`.
- DevTools → Application → Cache Storage: должны быть только `terminals-<BUILD_ID>` и `terminals-api-<BUILD_ID>` с одним и тем же свежим `BUILD_ID`. Всё остальное — зомби-кеши.
- Если нашли зомби — смотреть, что `activate`-handler на самом деле делает (не задеплоен ли старый SW без обновления шаблона).

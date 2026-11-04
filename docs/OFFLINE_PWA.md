---
name: OFFLINE_PWA
description: Offline-очередь визитов (IndexedDB, идемпотентность по client_uuid, retry), Service Worker, автообновление PWA и устойчивость auth к reload; читать при правках Service.vue, offlineQueue, sw.template.js, bootstrap.js.
type: project
---
# Offline-очередь визитов и Service Worker

Операторы работают с плохим интернетом: приложение устойчиво к зависшим запросам и отсутствию сети, а новый бандл подхватывается без действий оператора.

## Ограничения (нарушение ломает механику)

- Визит сначала в IndexedDB, потом сеть (`Service.vue::saveVisit`: `enqueue` предшествует `syncAll`). Иначе обрыв связи теряет визит.
- Идемпотентность по `client_uuid` держится на трёх слоях: `id` записи IndexedDB = `client_uuid` (`offlineDb.js::savePendingVisit`, `offlineQueue.js::buildFormData`); ранний возврат существующего визита и `UniqueConstraintViolationException` в `ServiceVisitController::store`; unique-индекс миграции `2026_05_01_000000_add_client_uuid_to_service_visits`. Сломать любой слой — дубль `ServiceVisit` и повторное уведомление при потере 201-ответа.
- `client_uuid` nullable; `ServiceVisitController::update` его не использует: редактирование визита идемпотентностью не защищено.
- После `MAX_SYNC_ATTEMPTS` (`offlineQueue.js`) `syncAll` пропускает визит; без `retryAll()` (`resetAllSyncAttempts` + `syncAll`) он не уйдёт никогда.
- Сетевая ошибка без `response` прерывает `syncAll` и не логируется; серверная — помечает визит и идёт дальше (`offlineQueue.js::syncAll`).
- 419 — запрос `csrf-cookie` и одна повторная попытка (`offlineQueue.js::syncAll`).
- Имена кеша SW содержат `BUILD_ID`, `activate` чистит чужие кеши; `/api` не-GET — только сеть, offline даёт 503 JSON; `/build/*` — Cache First; `manifest.json` — только Network First (иначе закешируется манифест со ссылками на удалённые чанки) (`sw.template.js`).
- Без `reload` после `controllerchange` страница остаётся на старом коде из памяти (`bootstrap.js`, обработчик `controllerchange`). В `/service/*` reload откладывается до `router.afterEach`; без сети ждёт `online`.
- Auth: сбой сети в `auth.js::fetchUser` — восстановление из `localStorage` (`auth-user-cache`), на `/login` не выкидывать.
- Редактирование визита offline не поддерживается: синхронный POST с `_method=PUT`, без сети тост об ошибке (`Service.vue::saveVisit`).

## Offline-очередь

`Service.vue::saveVisit` (новый визит): `enqueue` → удаление черновика → `router.push('/')`; при сети фоновый `syncAll()` без `await`. Без сети визит остаётся в очереди, `Home.vue` показывает баннер «Ожидает отправки».

Компоненты (методы — `grep -n '^export' resources/js/services/offlineDb.js`, `grep -n "async " resources/js/stores/offlineQueue.js`):

- `resources/js/services/offlineDb.js` — IndexedDB `terminals-offline`, store `pendingVisits`, черновики.
- `resources/js/stores/offlineQueue.js` (Pinia) — очередь, `syncAll`, `retryAll`, `lastSyncError`.
- `resources/js/layouts/AppLayout.vue` — `backgroundFetch` раз в минуту, слушатель `online` → `syncAll`, слушатель `app:toast`.
- `resources/js/pages/Home.vue` — кликабельный баннер: `retryAll()` и тост; при ошибке подпись с `lastSyncError.message`.

Ловушка: `Service.vue` имеет свой `showToast(type, message)` с обратным порядком аргументов относительно `AppLayout.vue::showToast(message, type)`.

### Идемпотентность

`ServiceVisitController::store`: существующий `client_uuid` — возврат визита (200) без создания, фото, ротации и уведомлений (ранний return до `savePhoto`); иначе создание в `DB::transaction`, при гонке — ловля `UniqueConstraintViolationException` и возврат созданного. Клиент удаляет запись из IndexedDB как при обычном успехе. Поле `client_uuid` — `CHAR(36) NULLABLE UNIQUE`.

### Логирование ошибок sync

Серверная ошибка в `syncAll` — fire-and-forget `apiClient.post('/client-errors')` (`offlineQueue.js`, `safeTrim` режет `response_data` до 2000 символов). `source`: `offline-sync` или `sync-retry-419` (повтор после CSRF refresh). Поля `context` — в `offlineQueue.js`; формат и фильтр — `CLIENT_ERROR_LOGGING.md`.

## Service Worker

`resources/sw.template.js` → (`scripts/stamp-sw.cjs`, подставляет `BUILD_ID = Date.now()` при `npm run build` и `npm run dev`) → `public/sw.js` (в `.gitignore`). Кеши: `terminals-<BUILD_ID>`, `terminals-api-<BUILD_ID>`.

| Путь | Стратегия |
|------|-----------|
| `/api/*` GET | Network First → cache |
| `/api/*` не-GET | Network only, offline → 503 JSON |
| `navigate` | Network First → `/` из кеша |
| `/build/manifest.json`, `/build/.vite/manifest.json` | Network First |
| `/build/*` | Cache First (имена хэшированные) |
| остальное | Network First → cache |

Источник — `sw.template.js` (обработчик `fetch`).

## PWA auto-update

`resources/js/bootstrap.js`: `tryUpdate(registration)` вызывает `registration.update()`, кроме ночи. Ночь — 23:00–07:00 по Asia/Irkutsk (`isNightTime`, `OPERATORS_TZ`), экономия батареи. Триггеры: `setInterval` раз в час (`SW_UPDATE_INTERVAL_MS`), `visibilitychange` → visible, `online`.

`controllerchange`: на `/service/*` — флаг `pendingReload` и тост (CustomEvent `app:toast`); иначе `safeReload()` (reload только при `navigator.onLine`, иначе ждёт `online`). `applyPendingReloadIfSafe` (`bootstrap.js`) вызывается из `router.afterEach` (`resources/js/router/index.js`): при поднятом `pendingReload` и пути не `/service/*` — `safeReload()`.

Lazy-чанк не загрузился (`router.onError`, `router/index.js`): на `home` — reload, иначе `router.push('home')`.

| Сценарий | Когда применится обновление |
|----------|------------------------------|
| PWA развёрнута / появилась сеть | сразу (`visibilitychange` / `online`) |
| PWA постоянно на экране | не позже часа |
| `controllerchange` вне `/service/*` | сразу |
| `controllerchange` в `/service/*` | при выходе из формы |
| ночь 23:00–07:00 Иркутск | проверки пропускаются до 07:00 |

## Auth-store

`resources/js/stores/auth.js::fetchUser`: при ошибке и пустом `user` — `_loadFromStorage()`, ключ `USER_CACHE_KEY = 'auth-user-cache'`. Сессия: `SESSION_LIFETIME` в `.env.example` (см. `DEPLOY.md`, «Сессии и не-разлогинивание»).

## Если снова сломается

- DevTools (`chrome://inspect/#devices` для PWA на Android) → Application → Service Workers: `/sw.js`, `Status`, `Update`.
- Cache Storage: только `terminals-<BUILD_ID>` и `terminals-api-<BUILD_ID>` с одним свежим `BUILD_ID`; остальное удалить.
- IndexedDB → `terminals-offline` → `pendingVisits`: записи с `syncStatus` и ошибкой (`offlineDb.js::updateSyncStatus`, поле ошибки `syncError`).
- Админка `/admin/client-errors`, фильтр `source = 'offline-sync'`.

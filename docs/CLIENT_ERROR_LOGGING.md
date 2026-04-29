# Логирование клиентских ошибок

Цель: отлаживать проблемы оператора (особенно offline-sync визитов) **без физического доступа к телефону**. Фронт отправляет описание ошибки на сервер, админ видит её в `/admin/client-errors`.

## База

Миграция `database/migrations/2026_04_29_000000_create_client_error_logs_table.php`:

| Поле | Тип | Назначение |
|------|-----|-----------|
| `id` | bigint PK | |
| `user_id` | FK users (nullable, set null on delete) | кто словил ошибку |
| `source` | varchar(64) | категория (`offline-sync`, `sync-retry-419`, …) |
| `message` | text | читаемое описание |
| `context` | json (nullable) | произвольные данные для разбора |
| `url` | varchar(500) | URL клиента в момент ошибки |
| `user_agent` | varchar(500) | UA из заголовка |
| `ip` | varchar(45) | IP клиента |
| `created_at` | timestamp | время |

Индексы: `created_at`, `source`.

## Backend

`app/Http/Controllers/ClientErrorController.php` — методы:

| Метод | Маршрут | Middleware | Назначение |
|-------|---------|-----------|-----------|
| `store(Request)` | `POST /api/client-errors` | `auth:sanctum`, `active`, `throttle:60,1` | приём логов с фронта |
| `index(Request)` | `GET /api/admin/client-errors` | + `admin` | список, фильтр по `source` / `user_id`, лимит до 500 |
| `destroy(ClientErrorLog)` | `DELETE /api/admin/client-errors/{id}` | + `admin` | удаление одной записи |
| `clear(Request)` | `DELETE /api/admin/client-errors/clear` | + `admin` | очистить всё или старее N дней |

Валидация:
- `source` — required string max:64
- `message` — required string max:5000
- `context` — nullable array
- `url` — nullable string max:500

Сервер сам подставляет `user_id`, `user_agent` (обрезается до 500), `ip`, `created_at`. Возвращает `204 No Content`.

## Frontend (отправка)

`resources/js/stores/offlineQueue.js::recordError()`:

```js
apiClient
    .post('/client-errors', {
        source,
        message,        // [status] message или валидационная ошибка
        context: {
            visit_id, terminal_id, terminal_name,
            sync_attempts, status,
            response_data,  // обрезано до 2000 символов
            error_name, error_code, error_message,
        },
        url: window.location.href,
    })
    .catch(() => {});  // fire-and-forget
```

Логируется только при ошибках с `error.response` (т.е. сервер ответил, но не 2xx). Сетевые ошибки без response не пишутся — серверу не дойдут всё равно.

## Источники (`source`)

| Источник | Когда возникает |
|----------|----------------|
| `offline-sync` | основная серверная ошибка в `syncAll()` (включая 419 если первая попытка не починилась) |
| `sync-retry-419` | повторная попытка после CSRF refresh тоже упала |

Новые источники добавлять как уникальные строки — не создавать второй endpoint.

## Админка

`resources/js/pages/admin/ClientErrors.vue`, маршрут `/admin/client-errors`, пункт меню «Ошибки клиентов» в `AdminLayout.vue`.

Возможности:
- Список последних записей (по умолчанию 100, до 500).
- Фильтр по `source` (выпадающий список с уникальными значениями).
- На карточке: бейдж source, дата (Asia/Irkutsk), имя пользователя, message, url.
- Раскрытие карточки — `user_agent`, `ip`, `context` (форматированный JSON).
- Удаление одной записи (`DELETE /admin/client-errors/{id}`).
- «Очистить все» (`DELETE /admin/client-errors/clear`).

## Типичные кейсы для отладки offline-sync

| Что в `context.status` / `response_data` | Диагноз |
|------------------------------------------|---------|
| 422, в `errors` поле `terminal_id`/`visited_at`/etc | Валидация: данные визита битые, проверить как формируется FormData |
| 413 | Слишком большое фото — Apache/PHP лимит, сжатие на фронте не сработало |
| 500, в `response_data` SQL/исключение | Серверная регрессия |
| 419, CSRF retry тоже упал | Cookie `XSRF-TOKEN` не пробрасывается, проверить sanctum stateful domains |

## Что НЕ логируется

- Сетевые ошибки без `response` (нет смысла — сервер вне досягаемости).
- 401 от auth — обработка отдельно в auth-store, не считается «ошибкой sync».
- Ошибки самого `POST /client-errors` — `.catch(() => {})`, чтобы не зациклиться.

## Расширение на другие места

Если нужно добавить логирование из другого компонента (не offline-sync):

1. Используй существующий `apiClient.post('/client-errors', ...)`.
2. Уникальный `source` (например, `'photo-upload'`, `'auth-refresh'`).
3. Складывай в `context` всё, что поможет понять причину: статус, ID связанных сущностей, обрезанный response.

`throttle:60,1` (60 запросов в минуту с одного IP) рассчитан на нормальную нагрузку. Если в новой фиче возможен burst — стоит добавить локальную дедупликацию на стороне клиента, чтобы не упереться в лимит.

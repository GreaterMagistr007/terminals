---
name: CLIENT_ERROR_LOGGING
description: Приём клиентских ошибок (POST /api/client-errors), значения source, админка /admin/client-errors и диагностика offline-sync; читать при добавлении источников ошибок или разборе сбоев очереди.
type: project
---
# Логирование клиентских ошибок

Фронт отправляет описание ошибки на сервер, админ видит её в `/admin/client-errors` — отладка без доступа к телефону оператора.

## Ограничения

- `recordError` — fire-and-forget с `.catch(() => {})`: иначе ошибка самого лога зациклит отправку (`resources/js/stores/offlineQueue.js::recordError`).
- Лимиты: `response_data` до 2000 символов (`offlineQueue.js::safeTrim`), `message` max:5000, `source` max:64 (`ClientErrorController::store`). Превышение даёт 422 на логе и потерю записи.
- Сетевые ошибки без `response` не логируются (`offlineQueue.js::syncAll`): сервер недостижим, очередь запросов в офлайне разрастётся.
- Писать могут только аутентифицированные пользователи; `user_id` nullable, `set null` при удалении (миграция).
- `throttle:60,1` на `POST /client-errors` (`routes/api.php`): при burst-логировании нужна дедупликация на клиенте.
- Новые источники — новая строка `source`, а не второй endpoint.

## База и backend

- Схема — миграция `database/migrations/2026_04_29_000000_create_client_error_logs_table.php` (индексы `created_at`, `source`).
- Контроллер — `app/Http/Controllers/ClientErrorController.php` (`store`, `index`, `destroy`, `clear`); маршруты: `grep -n 'client-errors' routes/api.php`.
- `store`: валидация `source` required max:64, `message` required max:5000, `context` nullable array, `url` nullable max:500; сервер сам ставит `user_id`, `user_agent` (до 500), `ip`, `created_at`; ответ `204`.
- `index`: фильтры `source` / `user_id`, `limit` 1..500, по умолчанию 100. `clear`: параметр `older_than_days` (min:0), без него удаляет всё (UI вызывает без параметра).

## Источники (`source`)

Значения — контракт: по ним фильтруется админка. Использования: `grep -rn "'offline-sync'\|'sync-retry-419'" resources/js`.

| Источник | Когда возникает |
|----------|----------------|
| `offline-sync` | серверная ошибка в `syncAll()` |
| `sync-retry-419` | повторная попытка после CSRF refresh тоже упала |

При провале 419-retry создаются две записи: `sync-retry-419`, затем `offline-sync` (`offlineQueue.js::syncAll`).

## Админка

`resources/js/pages/admin/ClientErrors.vue`, маршрут `/admin/client-errors` (`resources/js/router/index.js`), пункт меню «Ошибки клиентов» в `resources/js/layouts/AdminLayout.vue`. Дата показывается в Asia/Irkutsk. Список, фильтр по `source`, раскрытие карточки (`user_agent`, `ip`, `context`), удаление одной записи и очистка.

## Типичные кейсы offline-sync

| `context.status` / `response_data` | Диагноз |
|------------------------------------|---------|
| 422, в `errors` поле `terminal_id`/`visited_at` и т.п. | Данные визита битые, проверить формирование FormData |
| 413 | Слишком большое фото — лимит Apache/PHP; фронт сжимает фото до 1920 px по ширине (`Service.vue::compressImage`) |
| 500, в `response_data` SQL/исключение | Серверная регрессия |
| 419, CSRF retry тоже упал | Не пробрасывается cookie `XSRF-TOKEN`, проверить sanctum stateful domains |

## Расширение на другие места

1. Использовать `apiClient.post('/client-errors', ...)`.
2. Уникальный `source` (например, `photo-upload`).
3. В `context` — статус, ID связанных сущностей, обрезанный response.

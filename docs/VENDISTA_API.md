---
name: VENDISTA_API
description: Контракт интеграции с Vendista API, который использует код (токен, /transactions, /terminals, время Europe/Moscow, result/status/reverse_id, пагинация, таймауты, ретраи); читать при правках VendistaService и синхронизации транзакций.
type: project
---
# Vendista API (Aplata.Server API)

Базовый URL: `https://api.vendista.ru:99`, задаётся `VENDISTA_API_URL` (`config/services.php`, значения — в `.env.example`). Логин и пароль — `VENDISTA_API_LOGIN`, `VENDISTA_API_PASSWORD`. Весь клиент — `app/Services/VendistaService.php`. Остальной API (код его не вызывает) — в файле частностей, последняя строка.

## Авторизация

- Токен: `GET /token?Login=..&Password=..` — `VendistaService::authenticate`. Токен берётся из поля `token` ответа.
- Хранение: `Cache::put('vendista_api_token', ...)` без TTL (`authenticate`); срок жизни токена коду неизвестен.
- Передача: query-параметром `token` в каждом запросе (`VendistaService::sendRequest`). Refresh-токена нет.
- Признак истечения — только HTTP 401: `VendistaService::request` сбрасывает кэш, получает токен заново и повторяет запрос один раз. Повторный 401 — общий путь ошибки: `Log::error`, результат `null`.
- 2FA не поддерживается: `verification_code_sended: true` в ответе `/token` — ошибка авторизации, `/token/verify` не вызывается.
- Ошибка авторизации (не 2xx на `/token`, 2FA, нет токена) — `Log::error` и сообщение первому активному админу с `telegram_id` (`VendistaService::handleAuthError`).
- Токен в query попадает в access-логи прокси и веб-серверов; код логирует только path и тело ответа, не URL.

## Вызываемые эндпоинты

| Эндпоинт | Метод кода | Параметры, которые шлёт код |
|---|---|---|
| `GET /token` | `VendistaService::authenticate` | `Login`, `Password` |
| `GET /transactions` | `VendistaService::getTransactions`, из синхронизации — `fetchAllTransactions` | `DateFrom`, `DateTo`, `PageNumber`, `ItemsOnPage` (100); `TermId` код при выборке не передаёт |
| `GET /terminals` | `VendistaService::fetchAllTerminals` | `PageNumber`, `ItemsOnPage` (100) |

Обёртки `getSalesTotals`, `getSalesList`, `getCommonReport` и метод `post` в `VendistaService` есть, вызовов нет.

Признак успешного тела ответа — поле `success`; ошибка: `{ "success": false, "error": "текст" }`. Суммы — в копейках.

## Время

Vendista принимает и отдаёт время в Europe/Moscow без смещения (`2026-03-19T10:30:00`). Код шлёт `DateFrom`/`DateTo` в Moscow и разбирает `time`, `reverse_time` ответа как Moscow с переводом в UTC (`VendistaService::mapTransactionRow`, окна — `syncTransactions`). Ошибка пояса сдвигает окно выборки и время транзакции на 3 часа. Все зоны проекта и места конвертации — в [TIMEZONES.md](./TIMEZONES.md).

## GET /transactions

Ответ (поля, которые читает код — `VendistaService::mapTransactionRow`):
```json
{
  "page_number": 1,
  "items_per_page": 100,
  "items_count": 1234,
  "items": [
    {
      "id": 999999,
      "term_id": 123,
      "sum": 5000,
      "time": "2026-03-19T10:30:00",
      "result": 1,
      "status": 1,
      "response_code": 0,
      "card_number": "****1234",
      "reverse_id": 0,
      "reverse_time": null
    }
  ],
  "success": true,
  "error": null
}
```

Отсутствующие поля код подставляет по умолчанию: `sum`, `result`, `status`, `response_code`, `reverse_id` — 0; `card_number`, `reverse_time` — `null`. Остальные поля ответа (`terminal_id`, `processing`, `machine_item` и др.) код не читает.

### Поля `result`, `status`, `reverse_id`, `reverse_time`

`result = 1` — операция дошла до процессинга и завершилась без ошибки. Дальше тип записи различает `status`:

| status | смысл | sum | reverse_id |
|---|---|---|---|
| 1 | обычная продажа (в т.ч. бонусная с `sum=0`) | ≥ 0 | 0 |
| 2 | оригинал, по которому позже был возврат | > 0 | `trans_id` возвратной записи |
| 4 | возвратная запись (отмена платежа) | < 0 (или 0) | `trans_id` оригинала |

`status = 5` встречается у `result ∈ {0, -1}` — отмена на уровне процессинга, сохраняется отдельной записью с отрицательной суммой.

`reverse_id != 0` стоит у обеих записей возвратной пары и ссылается на парный `trans_id`. `reverse_time` код читает, если поле пришло, но опираться на него нельзя: в данных Vendista оно пустое. Подсчёт стаканов — в [CUP_COUNTING.md](./CUP_COUNTING.md).

## Пагинация

Цикл по `PageNumber` с `ItemsOnPage=100` (`VendistaService::fetchAllTransactions`, `fetchAllTerminals`) идёт, пока накоплено меньше `items_count` и страница не пуста. Окно синхронизации режется на месячные чанки, чтобы запрос не упирался в таймаут API (`VendistaService::syncTransactions`). Фильтры и сортировку (`FilterText`, `OrderBy*` и др.) код не использует.

## Таймауты и ретраи

- Запросы с токеном: `timeout(60)`, `connectTimeout(15)` (`VendistaService::sendRequest`). `/token` — без явных таймаутов (дефолт Laravel, 30 с).
- Ретрай только в `fetchAllTransactions`: 3 попытки на `ConnectionException`, пауза `2 * номер попытки` секунд. Любой другой сбой запроса — `request` возвращает `null`, и `fetchAllTransactions` прерывает выборку целиком.

## Чего код не обрабатывает

- 429 и лимиты запросов: нет backoff и `Retry-After`; лимиты API не документированы.
- 5xx, 400, 403, 404: отдельной обработки нет, любой не-2xx кроме 401 — `Log::error` и `null`, без повтора.
- 2FA и `/token/verify`.
- Refresh и TTL токена: только сброс по 401.

Справочник остального API Vendista (код не вызывает): [VENDISTA_API_REFERENCE.md](./VENDISTA_API_REFERENCE.md).

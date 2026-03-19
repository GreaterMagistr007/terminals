# Vendista API (Aplata.Server API)

Документация: https://api.vendista.ru:99/swagger/index.html
Спецификация: https://api.vendista.ru:99/swagger/ru/swagger.json (OpenAPI 3.0.4, ~1.1 МБ)
Базовый URL: `https://api.vendista.ru:99`

## Авторизация

### Получение токена

```
GET /token?Login={login}&Password={password}
```

Ответ:
```json
{
  "verification_code_sended": false,
  "token": "<token>",
  "user_id": 123
}
```

Если `verification_code_sended: true` — включена 2FA, токен будет `null`. Подтверждение:

```
GET /token/verify?Code={code}
```

### Использование токена

Токен передаётся **query-параметром** во всех запросах:

```
GET /transactions?token=<token>&TermId=123&DateFrom=...
```

Refresh-токена нет. При истечении — повторный запрос `/token`.

---

## Транзакции

### GET /transactions — список транзакций за период

| Параметр | Тип | Описание |
|---|---|---|
| `TermId` | int64 | ID терминала (если не указан — все терминалы) |
| `OwnerId` | int32 | ID клиента |
| `DivisionId` | int32 | ID подразделения |
| `ProcessingIds` | array[int32] | Фильтр по процессингам |
| `DateFrom` | date-time | Начало периода |
| `DateTo` | date-time | Конец периода |
| + пагинация, фильтрация, сортировка (см. раздел ниже) |

Ответ:
```json
{
  "page_number": 1,
  "items_per_page": 50,
  "items_count": 1234,
  "items": [
    {
      "id": 999999,
      "term_id": 123,
      "terminal_id": "TID001",
      "sum": 5000,
      "time": "2026-03-19T10:30:00",
      "result": 0,
      "card_number": "****1234",
      "processing": 1,
      "machine_item": [
        { "machine_item_id": 1, "quantity": 1, "item_info": "Кофе Американо" }
      ]
    }
  ],
  "success": true,
  "error": null
}
```

Суммы в **копейках** (int32/int64).

### Другие эндпоинты транзакций

| Эндпоинт | Описание |
|---|---|
| `GET /transactions/csv` | Экспорт в CSV (те же параметры) |
| `GET /transactions/{id}` | Транзакция по ID |
| `GET /transactions/{id}/returns` | Возвраты по ID исходной транзакции |
| `POST /transactions/{id}/cancel` | Отмена транзакции |

---

## Продажи

### GET /sales/totals — итоги продаж по автоматам (основной)

| Параметр | Тип | Описание |
|---|---|---|
| `MachineId` | int32 | ID автомата (если не указан — все) |
| `DivisionId` | int32 | ID подразделения |
| `SellTypes` | array[int32] | 1=нал, 2=безнал, 3=внешний кредит, 4=бонусы, 99=неудачные |
| `ShowDeleted` | bool | Показывать удалённые автоматы |
| `IsReturn` | bool | Только возвраты (default false) |
| `Micromarket` | bool | Только микромаркеты |
| `DateFrom` / `DateTo` | date-time | Период |
| + пагинация |

Ответ:
```json
{
  "total": {
    "sum": 100000,
    "cash_count": 50,
    "cash_sum": 40000,
    "cashless_count": 100,
    "cashless_sum": 60000
  },
  "items": [
    {
      "machine_id": 1,
      "machine_name": "Кофейный аппарат #1",
      "machine_address": "ул. Ленина 10",
      "machine_model": "Necta Koro",
      "sum": 5000,
      "cash_count": 3, "cash_sum": 2000,
      "cashless_count": 5, "cashless_sum": 3000
    }
  ],
  "success": true
}
```

### Другие эндпоинты продаж

| Эндпоинт | Описание |
|---|---|
| `GET /sales/list` | Детальный список продаж (каждая продажа отдельно: товар, цена, тип оплаты, время) |
| `GET /sales/products` | Итоги по товарам (название, количество, сумма, % от общего) |
| `GET /sales/graph` | Данные для графиков (`day`, `sum`) |
| `GET /sales` | **Deprecated** — использовать `/sales/totals` |

---

## Сводный отчёт

### GET /reports/common — сводный отчёт по терминалам

Параметры: `TermId`, `OwnerId`, `DivisionId`, `ProcessingIds`, `DateFrom`, `DateTo` + пагинация.

Ответ:
```json
{
  "total": {
    "incoming_amount": 500000,
    "incoming_count": 100,
    "cancelled_amount": 5000,
    "cancelled_count": 2,
    "rejected_amount": 3000,
    "rejected_count": 1
  },
  "items": [
    {
      "terminal_id": 123,
      "tid": "TID001",
      "terminal_comment": "...",
      "incoming_amount": 50000,
      "incoming_count": 10,
      "cancelled_amount": 500,
      "cancelled_count": 1,
      "rejected_amount": 0,
      "rejected_count": 0
    }
  ]
}
```

### Другие отчёты (17 эндпоинтов)

| Эндпоинт | Описание |
|---|---|
| `GET /reports/transactions_duration` | Длительность транзакций (группировка: <5с, <10с, <20с, <30с, >30с) |
| `GET /reports/gsm_quality` | Качество GSM-связи |
| `GET /reports/operators/common` | Отчёт по операторам за период |
| `GET /reports/operators/groupbytime` | Операторы с группировкой по времени |
| `GET /reports/operators/now` | Операторы на текущую дату |
| `GET /reports/operators/scores` | Оценки операторов |
| `GET /reports/repairs/*` | 7 отчётов по ремонтам (общий, статусы, проблемы, запчасти, финансовый) |
| `GET /reports/terminals/service` | Для техподдержки |
| `GET /reports/tickets/types` | По типам заявок |
| `GET /reports/tickets/comments` | Комментарии заявок |

---

## Пагинация, фильтрация, сортировка

Единый паттерн для всех списковых эндпоинтов:

| Параметр | Тип | Описание |
|---|---|---|
| `PageNumber` | int32, min 1 | Номер страницы (с 1) |
| `ItemsOnPage` | int32, min 1 | Записей на странице |
| `FilterText` | string | Текст фильтра |
| `FilterType` | int | 0 = подстрока, 1 = точное совпадение |
| `FilterByColumn` | int32 | Номер колонки для фильтра |
| `OrderByColumn` | int32 | Номер колонки для сортировки (-1 = без сортировки) |
| `OrderDesc` | bool | true = по убыванию |

---

## Форматы и соглашения

- **Даты:** ISO 8601 (`2026-03-19T00:00:00`)
- **Суммы:** в копейках (int32/int64)
- **Формат запросов/ответов:** JSON
- **Авторизация:** query-параметр `token`

### HTTP-коды ошибок

| Код | Описание |
|---|---|
| 200 | Успех |
| 400 | Некорректные данные |
| 401 | Требуется авторизация |
| 403 | Нет прав |
| 404 | Не найдено |
| 429 | Rate limit (конкретные лимиты не документированы) |
| 500 | Ошибка сервера |

Формат ошибки: `{ "success": false, "error": "текст" }`

---

## Навигация по API (все группы)

52 группы, 244 эндпоинта. Ключевые для проекта Terminals:

### Данные и аналитика
| Группа | Эндпоинты | Описание |
|---|---|---|
| Transactions | 5 | Транзакции: список, CSV, детали, возвраты, отмена |
| Sales | 5 | Продажи: итоги, список, товары, графики |
| Reports | 17 | Сводные и аналитические отчёты |
| Encashment | 1 | Инкассации |
| Replenishments | 1 | Загрузки ингредиентов |
| Events | 1 | События автоматов |

### Справочники и управление
| Группа | Эндпоинты | Описание |
|---|---|---|
| Machines | 9 | Автоматы: CRUD, ингредиенты, MDB-цены |
| Terminals | 26 | Терминалы: CRUD, команды, настройки |
| Products | 6 | Товары: CRUD, матрицы |
| Ingredients | 5 | Ингредиенты: CRUD |
| Recipes | 5 | Рецепты: CRUD |
| Divisions | 5 | Подразделения: CRUD |
| MachineModels | 5 | Модели автоматов |
| MachineStates | 1 | Состояния автоматов |

### Пользователи и заявки
| Группа | Эндпоинты | Описание |
|---|---|---|
| Users | 10 | Пользователи: CRUD, пароль |
| Owners | 49 | Клиенты: CRUD, документы, ФНС |
| Tickets | 15 | Заявки: CRUD, комментарии, вложения |

### Финансы и оплата
| Группа | Эндпоинты | Описание |
|---|---|---|
| Subscriptions | 13 | Подписки, баланс, детализация |
| PayMaster | 9 | Платежи, пополнение, автоплатежи |
| BonusAccounts | 7 | Бонусные счета |

### Прочее
| Группа | Описание |
|---|---|
| Tid / Mid | Управление TID/MID |
| Kassas | Кассы (облачные ОФД) |
| NfcTags | NFC-метки |
| QrSettings / QrProviders / DiscountsQr | QR-оплата и скидки |
| Templates | Шаблоны настроек терминалов |
| Repairs / RepairItems | Ремонты и запчасти |
| Suppliers | Поставщики |

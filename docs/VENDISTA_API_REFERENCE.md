---
name: VENDISTA_API_REFERENCE
description: Справочник API Vendista, которое код проекта не вызывает (остальные эндпоинты transactions, sales, reports, фильтры, все группы API); читать при расширении интеграции новыми эндпоинтами.
type: reference
---
Справочник API Vendista, который код проекта не вызывает; источник — swagger поставщика, с кодом не сверялся.

Документация: https://api.vendista.ru:99/swagger/index.html
Спецификация: https://api.vendista.ru:99/swagger/ru/swagger.json (OpenAPI 3.0.4, ~1.1 МБ)
Используемая часть API (авторизация, `/transactions`, `/terminals`) описана в [VENDISTA_API.md](./VENDISTA_API.md).

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

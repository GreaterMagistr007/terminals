---
name: db_schema
description: Схема БД Terminals — таблицы, колонки, индексы, связи; генерируемый артефакт с маркером свежести
type: reference
---
# Схема БД
Регенерация: собрать заново по файлам database/migrations в порядке имён и связям в app/Models (запросы к БД запрещены)
Каталог миграций: database/migrations
Маркер свежести: последняя миграция `2026_05_01_000000_add_client_uuid_to_service_visits.php`, всего миграций 27

Колонки указаны в итоговом состоянии, с учётом всех alter-миграций. Таймстемпы `created_at`/`updated_at` есть, только если указаны.

## users
Пользователи (операторы и админы), вход через Telegram.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| name | string(255) | нет | | |
| telegram_id | string(255) | да | | unique |
| role | string(255) | нет | 'operator' | значения `app/Enums/UserRole.php` |
| is_active | boolean | нет | true | |
| remember_token | string(100) | да | | |
| created_at, updated_at | timestamp | да | | |

Модель: `app/Models/User.php`, связи: hasMany authTokens.

## auth_tokens
Одноразовые токены входа и приглашений.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| token | string(64) | нет | | unique |
| type | string(255) | нет | | bot_auth, invite (по комментарию миграции) |
| user_id | bigint unsigned | да | | FK → users.id, on delete cascade |
| telegram_id | string(255) | да | | |
| expires_at | timestamp | нет | | |
| used_at | timestamp | да | | |
| created_at, updated_at | timestamp | да | | |

Модель: `app/Models/AuthToken.php`, связи: belongsTo user.

## vendista_terminals
Терминалы (точки), синхронизируемые из Vendista.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| vendista_id | unsigned int | нет | | unique |
| comment | string(255) | да | | |
| vendista_machine_id | unsigned int | да | | |
| tid | string(255) | да | | |
| serial_number | string(255) | да | | |
| latitude | decimal(10,7) | да | | |
| longitude | decimal(10,7) | да | | |
| last_online_at | datetime | да | | |
| state | unsigned tinyint | нет | 0 | |

Модель: `app/Models/VendistaTerminal.php` (`$timestamps=false`), связи: hasOne settings (TerminalSetting); belongsToMany ingredients через terminal_ingredients (pivot sort_order); hasMany serviceVisits (FK terminal_id); hasOne latestVisit (latestOfMany visited_at); hasMany transactions (VendistaTransaction.term_id → vendista_id).

## terminal_settings
Локальные настройки терминала, 1:1 с vendista_terminals.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| vendista_terminal_id | bigint unsigned | нет | | unique; FK → vendista_terminals.id, on delete cascade |
| short_name | string(100) | да | | |
| hidden | boolean | нет | false | |
| uses_water | boolean | нет | true | |
| water_split | boolean | нет | false | |
| address | string(500) | да | | |
| latitude | decimal(10,7) | да | | |
| longitude | decimal(10,7) | да | | |
| warehouse_id | bigint unsigned | да | | FK → warehouses.id, on delete set null |

Модель: `app/Models/TerminalSetting.php` (`$timestamps=false`), связи: belongsTo vendistaTerminal, belongsTo warehouse.

## ingredients
Справочник ингредиентов.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| name | string(255) | нет | | unique |
| short_name | string(50) | да | | |
| unit | string(255) | нет | 'упаковка' | |
| cost_per_unit | decimal(10,2) | нет | 0 | |
| quantity_per_package | decimal(10,3) | нет | 1 | |
| quantity_per_box | unsigned int | да | | |
| cost_per_unit_in_box | decimal(10,2) | да | | |
| is_active | boolean | нет | true | |
| created_at, updated_at | timestamp | да | | |

Модель: `app/Models/Ingredient.php`, связи: hasMany warehouseStocks, receipts (IngredientReceipt), stockMovements.

## warehouses
Склады.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| name | string(255) | нет | | |
| is_default | boolean | нет | false | |

Модель: `app/Models/Warehouse.php` (`$timestamps=false`), связи: hasMany stocks (WarehouseStock), receipts (IngredientReceipt).

## warehouse_stocks
Остатки ингредиентов по складам.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| warehouse_id | bigint unsigned | нет | | FK → warehouses.id, on delete cascade |
| ingredient_id | bigint unsigned | нет | | FK → ingredients.id, on delete cascade |
| quantity | integer | нет | 0 | |
| created_at, updated_at | timestamp | да | | |

Составной unique: (warehouse_id, ingredient_id).
Модель: `app/Models/WarehouseStock.php`, связи: belongsTo warehouse, ingredient.

## ingredient_receipts
Приходы ингредиентов на склад.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| warehouse_id | bigint unsigned | нет | | FK → warehouses.id, on delete cascade |
| ingredient_id | bigint unsigned | нет | | FK → ingredients.id, on delete cascade |
| quantity_units | unsigned int | нет | | |
| cost_per_unit | decimal(10,2) | нет | | |
| source | string(255) | нет | | |
| note | text | да | | |
| created_at, updated_at | timestamp | да | | |

Модель: `app/Models/IngredientReceipt.php`, связи: belongsTo warehouse, ingredient.

## stock_movements
Журнал движений склада.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| ingredient_id | bigint unsigned | нет | | FK → ingredients.id, on delete cascade |
| user_id | bigint unsigned | да | | FK → users.id, on delete set null |
| type | string(255) | нет | | purchase, transfer, write_off (по комментарию миграции) |
| quantity | unsigned int | нет | | в минимальных единицах |
| from_warehouse_id | bigint unsigned | да | | FK → warehouses.id, on delete set null |
| to_warehouse_id | bigint unsigned | да | | FK → warehouses.id, on delete set null |
| cost_per_unit | decimal(10,2) | да | | |
| source | string(10) | да | | box, unit (по комментарию миграции) |
| reason | string(255) | да | | |
| note | text | да | | |
| created_at, updated_at | timestamp | да | | |

Составной индекс: (ingredient_id, created_at).
Модель: `app/Models/StockMovement.php`, связи: belongsTo ingredient, user, fromWarehouse, toWarehouse.

## terminal_ingredients
Pivot: набор ингредиентов терминала и их порядок.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| vendista_terminal_id | bigint unsigned | нет | | FK → vendista_terminals.id, on delete cascade |
| ingredient_id | bigint unsigned | нет | | FK → ingredients.id, on delete cascade |
| sort_order | unsigned int | нет | 0 | |

Составной unique: (vendista_terminal_id, ingredient_id).
Модели нет: используется через `VendistaTerminal::ingredients()`.

## service_visits
Визиты обслуживания.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| terminal_id | bigint unsigned | нет | | FK → vendista_terminals.id, on delete cascade |
| user_id | bigint unsigned | да | | FK → users.id, on delete set null |
| client_uuid | char(36) | да | | unique; idempotency-ключ клиента (`OFFLINE_PWA.md`) |
| visited_at | datetime | нет | | |
| water_main | decimal(3,1) | да | | |
| water_spare | decimal(3,1) | да | | |
| comment | text | да | | |
| latitude | decimal(10,7) | да | | |
| longitude | decimal(10,7) | да | | |
| created_at, updated_at | timestamp | да | | |

Составной индекс: (terminal_id, visited_at).
Модель: `app/Models/ServiceVisit.php`, связи: belongsTo terminal (VendistaTerminal), user; hasMany ingredients (ServiceVisitIngredient), photos (ServiceVisitPhoto).

## service_visit_ingredients
Сколько ингредиента привезено и сколько нужно привезти.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| service_visit_id | bigint unsigned | нет | | FK → service_visits.id, on delete cascade |
| ingredient_id | bigint unsigned | нет | | FK → ingredients.id, on delete cascade |
| brought | unsigned int | нет | 0 | |
| needed | unsigned int | нет | 0 | |

Модель: `app/Models/ServiceVisitIngredient.php` (`$timestamps=false`), связи: belongsTo visit, ingredient.

## service_visit_photos
Фото визита.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| service_visit_id | bigint unsigned | нет | | FK → service_visits.id, on delete cascade |
| type | string(20) | нет | | inside, outside, comment (по комментарию миграции) |
| path | string(255) | нет | | |
| original_name | string(255) | да | | |
| created_at, updated_at | timestamp | да | | |

Модель: `app/Models/ServiceVisitPhoto.php`, связи: belongsTo visit.

## vendista_transactions
Транзакции продаж из Vendista. Значения `result`, `status`, `reverse_id` — в `VENDISTA_API.md` и `CUP_COUNTING.md`.

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| trans_id | unsigned bigint | нет | | unique |
| term_id | unsigned bigint | нет | | index; ссылка на vendista_terminals.vendista_id без FK в БД |
| sum | integer | нет | 0 | |
| time | datetime | нет | | index; UTC (`TIMEZONES.md`) |
| result | tinyint signed | нет | 0 | |
| status | unsigned tinyint | нет | 0 | |
| response_code | unsigned smallint | нет | 0 | |
| card_number | string(20) | да | | |
| reverse_id | unsigned bigint | нет | 0 | |
| reverse_time | datetime | да | | |

Составной индекс: (term_id, time).
Модель: `app/Models/VendistaTransaction.php` (`$timestamps=false`), связи: belongsTo terminal (term_id → vendista_id).

## client_error_logs
Ошибки, присланные клиентом (`CLIENT_ERROR_LOGGING.md`).

| колонка | тип | null | default | примечание |
|---|---|---|---|---|
| id | bigint unsigned PK | нет | auto | |
| user_id | bigint unsigned | да | | FK → users.id, on delete set null |
| source | string(64) | нет | | index |
| message | text | нет | | |
| context | json | да | | |
| url | string(500) | да | | |
| user_agent | string(500) | да | | |
| ip | string(45) | да | | |
| created_at | timestamp | нет | CURRENT_TIMESTAMP | index; updated_at нет |

Модель: `app/Models/ClientErrorLog.php` (`$timestamps=false`), связи: belongsTo user.

## Служебные таблицы Laravel
- sessions — сессии (создаётся в миграции users)
- cache, cache_locks — кэш и блокировки
- jobs, job_batches, failed_jobs — очередь

## Особенности миграций
- Единственная миграция с ветвлением по драйверу БД (sqlite, mysql/mariadb, pgsql, иначе исключение) и единственная, меняющая данные: `2026_04_20_170000_fix_vendista_transactions_timezone.php` сдвигает `vendista_transactions.time` и `reverse_time` на −3 часа (down — на +3). Новая миграция с сырым SQL обязана работать и на SQLite (dev, тесты), и на MySQL (prod).
- `vendista_transactions.term_id` связан с `vendista_terminals.vendista_id` только в модели (`VendistaTransaction.php`), внешнего ключа в БД нет: удаление терминала транзакции не удаляет.

---
name: CUP_COUNTING
description: Как считаются стаканы по vendista_transactions — scope poured() (налитая вода) и paid() (деньги), семантика after/since; читать при любой правке подсчёта продаж или остатка воды.
type: project
---
# Подсчёт стаканов

Все запросы к `vendista_transactions` для подсчёта стаканов идут через query-scopes `App\Models\VendistaTransaction` (`app/Models/VendistaTransaction.php`). Список scope'ов: `grep -n 'function scope' app/Models/VendistaTransaction.php`.

## Ограничения

- Прямой `where('result', ...)` в контроллерах и командах не добавлять — определение «что считается стаканом» разойдётся между потребителями. Проверка: `grep -rn "where('result'" app routes` вне модели пуст.
- `poured()` = `result=1 AND status != 4` (`VendistaTransaction::scopePoured`), `paid()` = `result=1 AND reverse_id = 0` (`VendistaTransaction::scopePaid`).
- Оригинал возврата (`status=2`) нельзя исключать из `poured()`: `WaterCheck` занизит расход воды, и алёрт «мало воды» придёт поздно. Нельзя включать его в `paid()`: выручка завысится.
- `after` строго `>`, `since` включительно `>=` (`scopeAfter`, `scopeSince`): транзакция в ту же секунду, что и визит, уже учтена в зафиксированных остатках воды визита.
- Правка определения — scope и этот документ.

## Структура транзакции в Vendista

Значения задаёт API Vendista (`VENDISTA_API.md`; маппинг полей — `VendistaService::mapTransactionRow`). Поле `status` внутри `result = 1`:

| status | смысл | sum | reverse_id |
|---|---|---|---|
| 1 | обычная продажа (включая бонусную с `sum=0`) | ≥ 0 | 0 |
| 2 | оригинал, по которому позже оформлен возврат | > 0 | `trans_id` парного возврата |
| 4 | возвратная запись (отмена платежа) | < 0 (или 0) | `trans_id` оригинала |

`reverse_id` стоит у обеих записей возвратной пары. `result != 1` встречается у возвратов с `status=5`. Бонусные продажи: `result=1, status=1, sum=0`.

## poured и paid

| | poured | paid |
|---|---|---|
| status=1 (обычные + бонусы) | да | да |
| status=2 (оригиналы возвратов) | да | нет |
| status=4 (возвраты) | нет | нет |

- `poured()` — вода физически налита (оригинал возврата воду уже налил). Потребители: `grep -rn 'poured()' app` (`WaterCheck::calculateWaterMain` для алёрта; `Admin/VendistaTerminalController` — `sales_since_last_visit`, который `resources/js/services/waterEstimate.js` умножает на `WATER_PER_CUP_ML`).
- `paid()` — финансовая продажа, исключает обе стороны возвратной пары. Потребитель: `SalesController::today` (`grep -rn 'paid()' app`). Бонусные транзакции входят в `total_count`, но не в `total_sum` (`SUM(sum)`, `COUNT(*)` в `SalesController::today`).
- Начало суток в `SalesController::today` — `Carbon::now()->startOfDay()`, зона описана в `docs/TIMEZONES.md`.

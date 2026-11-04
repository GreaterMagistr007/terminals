---
name: TIMEZONES
description: Три часовых пояса проекта (UTC в БД, Europe/Moscow у Vendista, Asia/Irkutsk у операторов), места конвертации, граница суток продаж и ночные окна; читать при работе со временем, Vendista-синхронизацией и расписанием.
type: project
---
# Часовые пояса

Проект работает с тремя зонами одновременно; ошибка конвертации проявляется как смещение статистики. Миграция-исправление: `database/migrations/2026_04_20_170000_fix_vendista_transactions_timezone.php`. Все места: `grep -n "Europe/Moscow\|Asia/Irkutsk" -r app routes resources/js`.

## Правила

| Слой | Зона | Примечание |
|------|------|-----------|
| `config('app.timezone')` | `UTC` | `config/app.php`, не менять |
| БД | `UTC` | все `datetime` / `timestamp`; отдельной настройки зоны в `config/database.php` нет |
| Vendista API | `Europe/Moscow`, без TZ-метки | ответы и параметры `DateFrom` / `DateTo` (комментарии в `VendistaService::syncTransactions`, `::fetchLatestTransactions`) |
| Ввод визита (`datetime-local`) | `Asia/Irkutsk` | `Service.vue` (`nowIrkutsk`) и `ServiceVisitController::store` согласованы |
| UI оператора | `Asia/Irkutsk` | `Home.vue`, `ServiceHistory.vue`, `Service.vue`, `ClientErrors.vue` |
| `Schedule::command('water:check')` | `Asia/Irkutsk`, hourly, окно 09:00-23:59 | `routes/console.php`; запуски 09:00..23:00 |
| Граница суток на странице продаж | UTC | `Carbon::now()->startOfDay()` в `SalesController::today` (`app/Http/Controllers/SalesController.php`) при `config/app.php` timezone=UTC: сутки начинаются в 00:00 UTC = 08:00 по Asia/Irkutsk |
| Ночное окно Service Worker | `Asia/Irkutsk`, 23:00–07:00 | `resources/js/bootstrap.js` (`isNightTime`, `OPERATORS_TZ`), в окне проверки обновлений пропускаются |

## Ограничения

- Vendista → БД: `Carbon::parse($item['time'], 'Europe/Moscow')->utc()` в `VendistaService::upsertTransactions` (`time`, `reverse_time`) и `::mapTransactionRow`; для `last_online_at` — в синхронизации терминалов (`VendistaService`, парсинг `last_online_time`). Без явной зоны Carbon читает строку как UTC: время хранится со сдвигом +3 ч, `MAX(time)` оказывается позже `now()`.
- БД → Vendista: `->timezone('Europe/Moscow')->format(...)` в `VendistaService::syncTransactions` и `::fetchLatestTransactions`.
- Визит: `Carbon::parse($validated['visited_at'], 'Asia/Irkutsk')->utc()` в `ServiceVisitController::store` и `::update`; вывод в Telegram — `timezone('Asia/Irkutsk')->format('d.m.Y H:i')`. Клиент и сервер обязаны использовать одну зону.
- `water:check` не должен запускаться вне 09:00-23:59 Иркутска (`routes/console.php`), иначе ночные алёрты уйдут в Telegram-группу.

## Если ломается снова

Признаки:
- «Продажи с последнего обслуживания» на `Home.vue` завышены.
- `MAX(time)` в `vendista_transactions` позже `now()`.
- Уровень воды на `Home.vue` занижен (учтены лишние транзакции).

Проверка (tinker, обращается к Vendista по сети; `VendistaService::get` публичный):

```php
$data = app(\App\Services\VendistaService::class)->get('/terminals', ['PageNumber'=>1, 'ItemsOnPage'=>1]);
echo $data['items'][0]['last_online_time'];  // MSK, на 3 ч впереди now() UTC
echo now();                                  // UTC
echo \App\Models\VendistaTransaction::max('time');  // UTC, <= now()
```

Если `MAX(time)` больше `now()` — зона слетела при записи; смотреть `VendistaService::upsertTransactions` / `::mapTransactionRow`. Определение стакана — `CUP_COUNTING.md`.

# Часовые пояса

Проект работает с тремя разными зонами одновременно. Ошибки в конвертации приводят к смещениям, которые визуально проявляются как «битая» статистика — см. баг-фикс `2026_04_20_170000_fix_vendista_transactions_timezone`.

## Правила

| Слой | Зона | Примечание |
|------|------|-----------|
| `config('app.timezone')` | `UTC` | Не менять. Laravel хранит все datetime в UTC. |
| БД (MySQL/sqlite) | `UTC` | Все `datetime` / `timestamp` поля. |
| Vendista API | `Europe/Moscow`, без TZ-метки | И в ответах, и в параметрах `DateFrom` / `DateTo`. |
| Ввод визита обслуживания (`<input type="datetime-local">`) | `Asia/Irkutsk` | Оператор вводит локальное время точки. |
| UI приложения | `Asia/Irkutsk` | Форматирование для оператора. |
| `Schedule::command('water:check')` | `Asia/Irkutsk`, окно 09:00-23:59 | Без этого уведомления шли круглосуточно. |

## Конвертация

### Vendista → БД

`VendistaService::upsertTransactions()` и `::mapTransactionRow()`:

```php
Carbon::parse($item['time'], 'Europe/Moscow')->utc()
```

Для `reverse_time` и `last_online_at` — аналогично.

### БД → Vendista (формирование `DateFrom`/`DateTo`)

`syncTransactions()` / `fetchLatestTransactions()`:

```php
$chunkStart->copy()->timezone('Europe/Moscow')->format('Y-m-d\TH:i:s')
```

### Визит обслуживания

`ServiceVisitController::store()` / `::update()`:

```php
Carbon::parse($validated['visited_at'], 'Asia/Irkutsk')->utc()
```

Отображение для Telegram-уведомления:

```php
$visit->visited_at->timezone('Asia/Irkutsk')->format('d.m.Y H:i');
```

## Почему именно так

Vendista — российский процессинг, сервер возвращает время по Москве и не ставит TZ-метку. Обнаружено эмпирически: raw `last_online_time` из API опережал `now()` UTC ровно на +3 часа. Ранее `Carbon::parse($item['time'])` без явной зоны интерпретировал строку как UTC (дефолт Laravel), что приводило к смещению хранимого времени на +3 часа относительно реального UTC.

Визиты пишутся операторами в точках Иркутска, поэтому ввод — в Иркутске. Хранение — в UTC (требование стандартной архитектуры Laravel).

`water:check` ночью никому не полезен: уведомления в Telegram-группе приходят в 01:00 / 02:00 / 03:00 и только раздражают. Окно 09:00-23:59 по Иркутску — последний запуск в 23:00 вечера, следующий — только в 09:00.

## Если ломается снова

Признаки:
- «Продажи с последнего обслуживания» на Home.vue показывают завышенное число.
- В `MAX(time)` таблицы `vendista_transactions` дата в будущем относительно `now()`.
- Уровень воды на Home.vue рассчитан заниженно (потому что включены лишние транзакции).

Проверка:

```php
// raw из API
$data = app(\App\Services\VendistaService::class)->get('/terminals', ['PageNumber'=>1, 'ItemsOnPage'=>1]);
echo $data['items'][0]['last_online_time'];  // должен быть MSK, +3 от now() UTC

echo now();                                                   // UTC
echo \App\Models\VendistaTransaction::max('time');            // UTC, <= now()
```

Если `MAX(time)` больше `now()` — значит где-то в записи в БД зона слетела. См. `VendistaService::upsertTransactions()` / `::mapTransactionRow()`.

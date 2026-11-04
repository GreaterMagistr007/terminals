---
name: operations
description: Куда смотреть, когда в Terminals что-то сломалось: логи, клиентские ошибки, фоновые процессы, расписание, health-check
type: project
---
# Эксплуатация

## Где смотреть
- **Логи сервера:** `storage/logs/laravel.log` (канал `single` через `stack`, `config/logging.php`, `.env.example`).
- **Ошибки с телефонов операторов:** админка `/admin/client-errors`. Что туда пишется и как отлаживать — `CLIENT_ERROR_LOGGING.md`.
- **Sentry** не подключён (`grep -ri sentry composer.json package.json config` пусто).
- **Health-check:** `GET /up` (`bootstrap/app.php`).

## Что работает в фоне
- **Транзакции Vendista подтягивает открытая PWA, а не сервер.** `resources/js/layouts/AppLayout.vue` раз в минуту при наличии сети вызывает `POST /api/vendista/transactions/fetch` и заодно запускает синхронизацию offline-очереди. Если приложение не открыто ни у кого, новые транзакции не загружаются. Пропуски догружаются командой `vendista:sync-transactions` (`workflow.md`).
- **Расписание** (`php82 artisan schedule:list`): `water:check`, ежечасно с 09:00 до 23:59 по `Asia/Irkutsk` (`routes/console.php`). На проде его запускает системный cron через `schedule:run` (`DEPLOY.md`).
- **Очереди:** `QUEUE_CONNECTION=database`, но воркеров на проде нет (`DEPLOY.md`). Код, который полагается на фоновую очередь, на проде не выполнится.
- **Бот Telegram:** сообщения боту принимает процесс `telegram:poll` (long polling, вебхука нет). `deploy.sh` его не перезапускает (`grep -n poll deploy.sh` пусто); как он запущен на проде, в репозитории не зафиксировано.
- **Прокси до Telegram:** `ss-local` (systemd) на сервере, настройка — `TELEGRAM_PROXY.md`.

## Типовые поломки
- Деплой и кеши, сессии, Service Worker — раздел «Типичные проблемы» в `DEPLOY.md`.
- Визит не ушёл с телефона, PWA не обновилась — `OFFLINE_PWA.md`, раздел «Если снова сломается».
- Время транзакций или визитов сдвинуто — `TIMEZONES.md`.
- Telegram не отвечает — `TELEGRAM_PROXY.md`, раздел «Типовые ошибки».

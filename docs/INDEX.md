# Документация Terminals

Оглавление документации проекта. Каждая тема — отдельный файл.

## Содержание

| Документ | Описание |
|---|---|
| [PROJECT.md](./PROJECT.md) | Требования к проекту: описание, стек, роли, модель данных, функциональные требования, решения по архитектуре |
| [ROADMAP.md](./ROADMAP.md) | План реализации по этапам (0–11), текущий статус, критерии проверки каждого этапа |
| [DEPLOY.md](./DEPLOY.md) | Развёртывание: prod-среда (PHP 8.5, без composer/npm на хостинге), `deploy.sh`, симлинк storage, симптомы и лечение типовых проблем |
| [TIMEZONES.md](./TIMEZONES.md) | Часовые пояса в разных слоях (UTC в БД, Europe/Moscow для Vendista, Asia/Irkutsk для визитов и UI) |
| [OFFLINE_PWA.md](./OFFLINE_PWA.md) | Offline-очередь визитов (IndexedDB), manual retry, Service Worker, **PWA auto-update** (controllerchange + интервалы + ночные паузы), устойчивость auth-store к reload |
| [CLIENT_ERROR_LOGGING.md](./CLIENT_ERROR_LOGGING.md) | Эндпоинт `/api/client-errors` и админка `/admin/client-errors`: отладка проблем оператора без доступа к телефону |
| [VENDISTA_API.md](./VENDISTA_API.md) | Vendista API: авторизация, транзакции, продажи, отчёты, навигация по 244 эндпоинтам |
| [CUP_COUNTING.md](./CUP_COUNTING.md) | Подсчёт стаканов: scope'ы `poured`/`paid` в `VendistaTransaction`, структура `status`/`reverse_id`, разделение «налито» vs «оплачено» |
| [TELEMETRON_REFERENCE.md](./TELEMETRON_REFERENCE.md) | Telemetron: полный справочник функционала, API, пересечения с проектом |
| [TELEGRAM_PROXY.md](./TELEGRAM_PROXY.md) | SOCKS5-прокси для Telegram Bot API на серверах в РФ; отправка фото через multipart |

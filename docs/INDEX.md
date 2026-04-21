# Документация Terminals

Оглавление документации проекта. Каждая тема — отдельный файл.

## Содержание

| Документ | Описание |
|---|---|
| [PROJECT.md](./PROJECT.md) | Требования к проекту: описание, стек, роли, модель данных, функциональные требования, решения по архитектуре |
| [ROADMAP.md](./ROADMAP.md) | План реализации по этапам (0–11), текущий статус, критерии проверки каждого этапа |
| [DEPLOY.md](./DEPLOY.md) | Развёртывание: prod-среда (PHP 8.3, пути), `deploy.sh`, `.git-token`, релизный цикл, доставка фронта, SW-инвалидация |
| [TIMEZONES.md](./TIMEZONES.md) | Часовые пояса в разных слоях (UTC в БД, Europe/Moscow для Vendista, Asia/Irkutsk для визитов и UI) |
| [OFFLINE_PWA.md](./OFFLINE_PWA.md) | Offline-очередь визитов (IndexedDB) и Service Worker: стратегии кеша, версионирование через BUILD_ID |
| [VENDISTA_API.md](./VENDISTA_API.md) | Vendista API: авторизация, транзакции, продажи, отчёты, навигация по 244 эндпоинтам |
| [TELEMETRON_REFERENCE.md](./TELEMETRON_REFERENCE.md) | Telemetron: полный справочник функционала, API, пересечения с проектом |
| [TELEGRAM_PROXY.md](./TELEGRAM_PROXY.md) | SOCKS5-прокси для Telegram Bot API на серверах в РФ: зачем, как настроить, как проверить; отправка фото через multipart (обход `WEBPAGE_CURL_FAILED`) |

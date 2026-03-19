# Terminals

PWA для учёта обслуживания кофейных аппаратов.

## Стек

- **Бэкенд:** PHP 8.2, Laravel 12
- **Фронтенд:** Vue 3.5, Tailwind CSS 4, Vite 7
- **Авторизация:** Laravel Sanctum 4 (stateful SPA, cookie-based), Telegram Bot
- **БД:** SQLite (dev), MySQL (prod)
- **Формат:** PWA, offline-first

## Быстрый старт

```bash
composer setup    # установка зависимостей, ключ, миграции, сборка фронтенда
composer dev      # запуск dev-окружения (сервер + очередь + vite)
composer test     # запуск тестов
```

## Документация

Вся документация находится в директории [`docs/`](./docs/).

- **[docs/INDEX.md](./docs/INDEX.md)** — оглавление документации
- **[docs/PROJECT.md](./docs/PROJECT.md)** — требования к проекту, модель данных, решения по архитектуре
- **[docs/ROADMAP.md](./docs/ROADMAP.md)** — план реализации по этапам, текущий статус

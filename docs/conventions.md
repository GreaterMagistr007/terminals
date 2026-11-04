---
name: conventions
description: Соглашения кода Terminals, которых не проверяет .editorconfig: где чему место, HTTP-клиент, роли, язык, время
type: project
---
# Соглашения
Форматирование задаёт `.editorconfig`, конфигов другого линтера в проекте нет (`ls pint.json .eslintrc* .prettierrc* phpstan.neon*` пусто).

- **HTTP с фронта — только через `apiClient`** (`resources/js/api/client.js`: baseURL `/api`, cookie, XSRF, повтор при 419). Прямой `import axios` в других файлах не используется (`grep -rn "from .axios." resources/js`).
- **Обращения к внешним системам — в сервисах `app/Services`** (`VendistaService`, `TelegramService`, `AuthService`). Контроллеры и команды получают сервис через DI.
- **Роли — enum `App\Enums\UserRole`.** Доступ задают middleware `active` (на всех авторизованных API) и `admin` (на `/api/admin/*`), см. `routes/api.php`.
- **Язык:** комментарии, сообщения API и UI — на русском.
- **Время:** в БД и в коде сервера — UTC, у Vendista — `Europe/Moscow`, у визитов и в UI — `Asia/Irkutsk`. Конвертации и правила — `TIMEZONES.md`.
- **Сообщения в Telegram с фото — только multipart** (`TELEGRAM_PROXY.md`).
- **Подсчёт стаканов — только через scope'ы `VendistaTransaction`** (`CUP_COUNTING.md`).
- **Миграции работают и на SQLite, и на MySQL** (`db_schema.md`, раздел «Особенности миграций»).
- **Тесты не обращаются к БД** — `workflow.md`, раздел «Тесты».

---
name: workflow
description: Рабочий контур Terminals: стек, точки входа, команды, секреты, тесты, куда не ходить
type: project
---
# Рабочий контур

## Стек и версии
- Backend: PHP ^8.2, Laravel ^12, Sanctum ^4 (stateful SPA, cookie). Версии — `composer.json`.
- Frontend: Vue 3, vue-router, pinia, axios, Vite 7, Tailwind 4. Версии — `package.json`.
- PHP локально вызывается алиасом `php82`; на проде PHP 8.5 по пути `/opt/php85/bin/php` (`deploy.sh`). На проде нет composer и Node — подробности в `DEPLOY.md`.
- БД: локально и в тестах SQLite, на проде MySQL (`.env.example`, `phpunit.xml`, `DEPLOY.md`).

## Точки входа
- Backend: `bootstrap/app.php` — маршруты, health-check `/up`, алиасы middleware `active` и `admin`, JSON-ошибки для `api/*`. Перечень API: `php82 artisan route:list --path=api`.
- SPA: `routes/web.php` отдаёт view `app` на любой путь, кроме `api/*`. Фронт: `resources/js/app.js`, маршруты `resources/js/router/index.js`.
- Service Worker: исходник `resources/sw.template.js`. `scripts/stamp-sw.cjs` подставляет BUILD_ID и пишет `public/sw.js`. Правится только шаблон.
- Консоль: `app/Console/Commands` (`php82 artisan list`). Расписание — `routes/console.php`.

## Команды
| Что | Команда | Откуда взята |
|---|---|---|
| первичная установка | `composer setup` | `composer.json` scripts.setup |
| dev-окружение (сервер, очередь, логи, vite) | `composer dev` | `composer.json` scripts.dev |
| тесты | `composer test` | `composer.json` scripts.test |
| сборка фронта и SW | `npm run build` | `package.json` scripts.build |
| миграции | `php82 artisan migrate` | Laravel; на проде `deploy.sh` |
| первый админ | `php82 artisan app:create-admin "Имя" --telegram-id=ID` | `app/Console/Commands/CreateAdmin.php` |
| приём сообщений бота | `php82 artisan telegram:poll` | `app/Console/Commands/TelegramPoll.php` |
| догрузка транзакций Vendista за период | `php82 artisan vendista:sync-transactions --from=YYYY-MM-DD --to=YYYY-MM-DD` | `app/Console/Commands/VendistaSyncTransactions.php` |
| линтер | отдельного нет; Pint есть в require-dev без `pint.json` | `composer.json` |
| деплой | только владелец, `DEPLOY.md` | `deploy.sh` |

## Секреты
- `.env` локально и на проде, `.git-token` (GitHub PAT) только на проде. Оба в `.gitignore`.
- Новая переменная окружения добавляется в `.env.example`, `.env` не трогать. Назначение неочевидных переменных описано комментариями в самом `.env.example`.
- На проде после правки `.env` нужен `config:cache` (`DEPLOY.md`).

## Тесты
- `tests/Unit`, `tests/Feature` (подкаталоги `Stage0`, `Stage1`), запуск `composer test`. `phpunit.xml` подставляет SQLite `:memory:`.
- **Новые тесты к БД не обращаются никак, включая SQLite `:memory:`.** Данные хардкодятся прямо в тесте, слой доступа к данным мокается (Mockery есть в require-dev). Существующие тесты с `RefreshDatabase` (`grep -rl RefreshDatabase tests`) — унаследованные, образцом не служат.
- Сервисы Vendista, визиты и подсчёт воды тестами не покрыты: `find tests -name '*.php'`.

## Куда не ходить по умолчанию
`vendor`, `node_modules`, `.git`, `public/build`, `public/build.zip`, `public/sw.js` (генерируется), `storage`, `bootstrap/cache`, `.idea`; каталог `.git (2)` в корне (посторонний git-каркас, не часть проекта); `resources/js/pages/demo/` (варианты дизайна, маршруты `/demo/*`).
Запрет не абсолютный: если есть обоснованный довод, что проблема в стороннем пакете, идти туда нужно.

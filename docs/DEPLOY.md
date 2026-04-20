# Развёртывание и деплой

## Прод-среда

- Хост: `pkfsb`, пользователь `insite`.
- Каталог проекта: `~/www/termials.in-site.ru` (симлинк → `/var/www/insite/data/www/termials.in-site.ru`). Опечатка в имени намеренная.
- PHP 8.3: `/opt/php83/bin/php`. Системный `/usr/bin/php` — 8.1, `composer.lock` требует `>=8.2`, использовать нельзя.
- Composer: `$HOME/bin/composer` (установлен локально через `getcomposer.org/installer` под PHP 8.3). Глобального нет.
- Node / npm: **отсутствуют**. Фронт собирается на машине разработчика, артефакты переносятся вручную.
- БД: MySQL. Локально у разработчика — sqlite, миграции где важно поддерживают оба драйвера (пример — `2026_04_20_170000_fix_vendista_transactions_timezone`).
- Системный `cron` запускает `schedule:run`.

## Репозиторий

- Remote: `https://github.com/GreaterMagistr007/terminals.git`.
- Основная ветка: `master`.
- Личный токен (PAT) на сервере лежит в файле `.git-token` в корне проекта (`chmod 600`, в `.gitignore`).
- Ротация: `echo 'ghp_...' > .git-token` — код править не нужно.
- GitHub Push Protection активен: любые PAT/секреты в коммитах блокируются на стороне remote. Прямой хардкод токенов запрещён — только через `.git-token`.

## Скрипт `deploy.sh`

Расположен в корне. Основные шаги (в порядке выполнения):

1. `export PATH="$HOME/bin:/opt/php83/bin:$PATH"` — поднимаем PHP 8.3 и локальный composer в `PATH`.
2. Читает `.git-token`, собирает URL вида `https://oauth2:$GIT_TOKEN@github.com/…` для этого pull (в `git config` токен не сохраняется).
3. `php artisan down --retry=5`.
4. `git pull --ff-only` по tokenized URL и имени текущей ветки.
5. `composer install --no-dev --optimize-autoloader --no-interaction`.
6. `php artisan migrate --force`.
7. `php artisan config:clear / cache:clear / route:clear / view:clear`, затем `config:cache / route:cache / view:cache`.
8. `php artisan up`.

`set -euo pipefail` — при любой ошибке скрипт останавливается. Если падает посередине, сайт остаётся в maintenance-режиме до ручного `php artisan up`. Это намеренно, чтобы не вернуть в прод полуподнятую версию.

`composer` и `npm` шаги не ускорены условиями «менялся lock?» — это безопасный дефолт. Frontend build намеренно не включён: на сервере нет node.

## Релизный цикл

### Backend-only изменение

1. Локально: `git push origin master`.
2. На сервере:
   ```bash
   cd ~/www/termials.in-site.ru
   ./deploy.sh
   ```

### С правкой фронта

1. Локально: `git push origin master`.
2. Локально: `npm run build`. Скрипт выполняет `vite build`, затем `node scripts/stamp-sw.cjs`.
3. На сервере: `./deploy.sh`.
4. Локально: копирование артефактов `public/` на сервер. Вариант:
   ```bash
   rsync -avz --delete public/build/ insite@pkfsb:~/www/termials.in-site.ru/public/build/
   rsync -avz public/sw.js insite@pkfsb:~/www/termials.in-site.ru/public/sw.js
   ```
   Обязательно приехать должны обновлённые `public/build/*` и `public/sw.js`.

## Первичная инициализация сервера

Если в каталоге нет `.git/`:

```bash
cd ~/www/termials.in-site.ru
echo 'ghp_…' > .git-token
chmod 600 .git-token

git init -b master
git remote add origin https://$(cat .git-token)@github.com/GreaterMagistr007/terminals.git
git fetch origin master
git reset --hard origin/master
# убираем токен из remote URL — он останется только в .git-token
git remote set-url origin https://github.com/GreaterMagistr007/terminals.git
git branch --set-upstream-to=origin/master master

chmod +x deploy.sh
./deploy.sh
```

`.env`, `storage/app/public/visits/*`, логи, сессии, `database/*.sqlite*` — всё в `.gitignore`, `git reset --hard` их не трогает.

## Service Worker и инвалидация кеша

`public/sw.js` генерируется при сборке — он в `.gitignore`.
Источник — `resources/sw.template.js` (коммитится).

```
resources/sw.template.js   ──(scripts/stamp-sw.cjs)──▶   public/sw.js
(плейсхолдер __BUILD_ID__)                              (заменён на timestamp)
```

В шаблоне:

```js
const BUILD_ID = '__BUILD_ID__';
const CACHE_NAME = 'terminals-' + BUILD_ID;
const API_CACHE_NAME = 'terminals-api-' + BUILD_ID;
```

Каждая сборка → новый `BUILD_ID` → новые байты `/sw.js` → браузер переустанавливает SW → `activate`-handler удаляет все кеши, не входящие в `[CACHE_NAME, API_CACHE_NAME]` с новым `BUILD_ID`. Старые хэшированные чанки `/build/*` уходят автоматически.

`resources/js/bootstrap.js` регистрирует SW с `{ updateViaCache: 'none' }` и вызывает `registration.update()` на каждое `visibilitychange → visible` — PWA, открытое у оператора часами, подхватит новую сборку при возврате фокуса на вкладку.

Стратегии в `sw.template.js`:

| Путь | Стратегия |
|------|-----------|
| `/api/*` GET | Network First + cache fallback |
| `/api/*` не-GET | Network only (при offline — 503 JSON) |
| `request.mode === 'navigate'` | Network First, fallback на кешированный `/` |
| `/build/manifest.json` и `/build/.vite/manifest.json` | Network First (важно — раньше тут был Cache First, запирал старый манифест) |
| `/build/*` | Cache First (имена хэшированные, cache-busting встроен) |
| остальное | Network First + cache |

## Артефакты, которые нельзя коммитить

| Файл | Почему |
|------|--------|
| `.env` | секреты |
| `.git-token` | GitHub PAT |
| `public/sw.js` | генерируется из шаблона при каждой сборке |
| `public/build/` | сборка Vite |
| `database/*.sqlite*` | локальная dev-БД |
| `vendor/`, `node_modules/` | deps |

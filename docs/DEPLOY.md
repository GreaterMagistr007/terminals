# Развёртывание и деплой

## Прод-среда

- Хост: `pkfsb`, пользователь `insite`.
- Каталог проекта: `~/www/termials.in-site.ru` (симлинк → `/var/www/insite/data/www/termials.in-site.ru`). Опечатка в имени намеренная.
- PHP 8.5: `/opt/php85/bin/php`. Системный `/usr/bin/php` старее, не подходит.
- **Composer: отсутствует** на хостинге. `vendor/` обновляется вручную с локальной машины.
- **Node / npm: отсутствуют**. Фронт собирается локально, артефакты `public/build/` и `public/sw.js` копируются вручную.
- БД: MySQL. Локально — sqlite. Миграции, где это важно, поддерживают оба драйвера.
- Системный `cron` запускает `schedule:run`.

## Репозиторий

- Remote: `https://github.com/GreaterMagistr007/terminals.git`.
- Основная ветка: `master`.
- Личный токен (PAT) на сервере лежит в файле `.git-token` в корне проекта (`chmod 600`, в `.gitignore`).
- Ротация: `echo 'ghp_...' > .git-token`.
- GitHub Push Protection активен: PAT/секреты в коммитах блокируются. Только через `.git-token`.

## Скрипт `deploy.sh`

Использует прямой путь `/opt/php85/bin/php` без модификаций PATH. Не вызывает ни `composer`, ни `npm`.

Шаги (в порядке):

1. Проверка наличия `.git-token` и `/opt/php85/bin/php`.
2. `php artisan down --retry=5` (фейл не валит деплой).
3. `git pull --ff-only` через токен из `.git-token`.
4. **Очистка `bootstrap/cache/{packages,services,config,routes-v7,events}.php`**. Принципиальный шаг: иначе закешированный `packages.php` может ссылаться на dev-провайдеры (Pail и пр.), отсутствующие в production-`vendor/`, и любой artisan-вызов падает с `Class Laravel\Pail\PailServiceProvider not found`.
5. `php artisan package:discover --ansi || true` — пересобирает `bootstrap/cache/packages.php` под актуальный `vendor/`.
6. `php artisan migrate --force`.
7. `clear` + `cache` для config / route / view.
8. `php artisan up`.

`set -euo pipefail` — при любой ошибке скрипт останавливается. Если упадёт между `down` и `up` — сайт остаётся в maintenance до ручного `/opt/php85/bin/php artisan up`.

### Что деплой **не** делает

- **Composer install** — composer на сервере отсутствует. Если меняется `composer.lock`, разработчик локально делает `composer install --no-dev --optimize-autoloader` и заливает `vendor/` через rsync.
- **Frontend build** — npm на сервере отсутствует. Сборка локально, артефакты копируются вручную.
- **Ручной `git pull`** — не нужен, `deploy.sh` уже делает pull внутри (шаг 3).
- **`php artisan queue:restart`** — нет фоновых воркеров.
- **Перезапуск PHP-FPM / сброс opcache** — `validate_timestamps=1`, FPM подхватывает `.php` по mtime. Если нужно вручную — через панель ispmanager.

## Релизный цикл

### Backend-only

```bash
# локально
git push origin master

# на сервере
cd ~/www/termials.in-site.ru && bash deploy.sh
```

### С правкой фронта

```bash
# локально
git push origin master
npm run build  # vite build + scripts/stamp-sw.cjs (новый BUILD_ID в public/sw.js)

# доставка фронта на сервер
rsync -avz --delete public/build/ insite@pkfsb:~/www/termials.in-site.ru/public/build/
rsync -avz public/sw.js insite@pkfsb:~/www/termials.in-site.ru/public/sw.js

# на сервере
cd ~/www/termials.in-site.ru && bash deploy.sh
```

### С обновлением composer-зависимостей

```bash
# локально
composer install --no-dev --optimize-autoloader
rsync -avz --delete vendor/ insite@pkfsb:~/www/termials.in-site.ru/vendor/

# дальше как обычно
```

`deploy.sh` сам сделает `package:discover` после смены `vendor/`.

## Первичная инициализация сервера

```bash
cd ~/www/termials.in-site.ru
echo 'ghp_…' > .git-token
chmod 600 .git-token

git init -b master
git remote add origin https://$(cat .git-token)@github.com/GreaterMagistr007/terminals.git
git fetch origin master
git reset --hard origin/master
git remote set-url origin https://github.com/GreaterMagistr007/terminals.git
git branch --set-upstream-to=origin/master master

chmod +x deploy.sh

# Залить vendor/ и public/build/, public/sw.js с локальной машины (rsync).
# Создать симлинк хранилища:
/opt/php85/bin/php artisan storage:link

bash deploy.sh
```

`.env`, `storage/app/public/visits/*`, логи, сессии, `database/*.sqlite*` — всё в `.gitignore`.

## Симлинк `public/storage`

Должен указывать на `/var/www/insite/data/www/termials.in-site.ru/storage/app/public`.

При ручной заливке файлов на сервер симлинк может оказаться кривым (например, ведёт на dev-путь разработчика). Без рабочего симлинка:
- Фото визитов через web (`Storage::url`, `<img src="/storage/...">`) **не отдаются** — Apache → 404.
- Telegram-уведомления работают (multipart-аплоад с локального пути файловой системы, не через URL).

Починка:
```bash
rm public/storage
/opt/php85/bin/php artisan storage:link
ls -la public/storage  # должно быть -> /var/www/.../storage/app/public
```

## Сессии и не-разлогинивание

- `SESSION_DRIVER=database`.
- `SESSION_LIFETIME=2628000` минут = 5 лет (idle-таймаут).
- При каждом /api запросе обновляется `last_activity` → активная PWA практически вечная.
- При смене значения в `.env` — обязательно `/opt/php85/bin/php artisan config:cache`.

См. `OFFLINE_PWA.md` про `auth-store` и fallback из `localStorage` при сетевых сбоях.

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

Каждая сборка → новый `BUILD_ID` → новые байты `/sw.js` → браузер ставит новый SW → `activate` удаляет все кеши, не входящие в `[CACHE_NAME, API_CACHE_NAME]` с актуальным `BUILD_ID`.

`resources/js/bootstrap.js` обеспечивает автоматическую установку обновлений на устройствах операторов — см. `OFFLINE_PWA.md` раздел «PWA auto-update».

## HTTP-кеширование (`public/.htaccess`)

```apache
# /sw.js и /manifest.json — никогда не кэшировать
<FilesMatch "^(sw\.js|manifest\.json)$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
</FilesMatch>

# /build/* — immutable на год (хеши в именах)
SetEnvIf Request_URI "^/build/" VITE_BUILD_ASSET
<FilesMatch "\.(js|css|woff2?|ttf|otf|svg|png|jpg|jpeg|webp|ico)$">
    Header set Cache-Control "public, max-age=31536000, immutable" env=VITE_BUILD_ASSET
</FilesMatch>
```

Без `no-cache` для `/sw.js` Apache мог отдавать его с ETag/Last-Modified, и обновлённый BUILD_ID мог не доезжать до клиента.

## Артефакты, которые нельзя коммитить

| Файл | Почему |
|------|--------|
| `.env` | секреты |
| `.git-token` | GitHub PAT |
| `public/sw.js` | генерируется из шаблона при каждой сборке |
| `public/build/` | сборка Vite |
| `database/*.sqlite*` | локальная dev-БД |
| `vendor/`, `node_modules/` | deps |

## Типичные проблемы

| Симптом | Причина | Решение |
|---------|---------|---------|
| `Class Laravel\Pail\PailServiceProvider not found` | Закешированный `packages.php` ссылается на dev-провайдер, отсутствующий в `vendor/` | `rm bootstrap/cache/packages.php && php artisan package:discover` (или просто `bash deploy.sh`) |
| Фото визитов не отображаются на сайте, в Telegram приходят | Сломан симлинк `public/storage` | `rm public/storage && php artisan storage:link` |
| После деплоя оператор продолжает работать на старом JS | SW обновился, но JS в памяти страницы — старый | Должен сработать auto-reload из `bootstrap.js` (см. OFFLINE_PWA). Если нет — закрыть/открыть PWA |
| Оператора выкинуло на /login | Сессия истекла (5 лет idle) или cookie повреждён | Перелогин. Проверить `SESSION_LIFETIME` в `.env` |

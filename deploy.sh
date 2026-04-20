#!/usr/bin/env bash
# Деплой проекта на prod: git pull, composer, frontend-build, миграции, сброс и прогрев кэшей.
# Запуск: ./deploy.sh
# GitHub PAT читается из файла .git-token рядом со скриптом (файл в .gitignore).

set -euo pipefail

# На проде PHP 8.3 лежит в /opt/php83/bin. Префиксуем PATH, чтобы php/composer/artisan
# подхватывали именно его, а не системный /usr/bin/php (обычно 8.1).
# $HOME/bin — типичное место для локально установленных composer/nvm-shim и т.п.
export PATH="$HOME/bin:/opt/php83/bin:$PATH"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# GitHub Personal Access Token читаем из файла .git-token (он в .gitignore).
# На новом сервере создать: echo 'ghp_...' > .git-token && chmod 600 .git-token
if [[ ! -r .git-token ]]; then
    echo "ERROR: файл .git-token не найден или недоступен для чтения" >&2
    echo "Создай его: echo 'ghp_...' > .git-token && chmod 600 .git-token" >&2
    exit 1
fi
GIT_TOKEN="$(tr -d '[:space:]' < .git-token)"

echo "==> Режим обслуживания"
php artisan down --retry=5 || true

echo "==> git pull"
ORIG_URL="$(git remote get-url origin)"
# Нормализуем URL к виду host/path (поддерживаем https://... и git@host:path)
HOST_PATH="$(printf '%s' "$ORIG_URL" | sed -E 's|^https?://([^@]+@)?||; s|^git@([^:]+):|\1/|')"
TOKEN_URL="https://oauth2:${GIT_TOKEN}@${HOST_PATH}"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
git pull --ff-only "$TOKEN_URL" "$BRANCH"

echo "==> composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> frontend build"
npm ci --no-audit --no-fund
npm run build

echo "==> migrations"
php artisan migrate --force

echo "==> clear + recache"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> up"
php artisan up

echo "==> deploy OK"

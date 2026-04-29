#!/usr/bin/env bash
# Деплой проекта на prod: git pull, миграции, сброс и прогрев кэшей.
# Composer на хостинге недоступен -- vendor/ обновляется вручную (rsync/scp/архив).
# Запуск: ./deploy.sh
# GitHub PAT читается из файла .git-token рядом со скриптом (файл в .gitignore).

set -euo pipefail

# Полный путь к PHP 8.5 на хостинге pkfsb. Системный /usr/bin/php обычно старее.
PHP="/opt/php85/bin/php"

# $HOME/bin -- локальные шимы (на всякий случай для git и пр.).
export PATH="$HOME/bin:$PATH"

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

if [[ ! -x "$PHP" ]]; then
    echo "ERROR: $PHP не найден или не исполняемый" >&2
    exit 1
fi

echo "==> Режим обслуживания"
"$PHP" artisan down --retry=5 || true

echo "==> git pull"
ORIG_URL="$(git remote get-url origin)"
# Нормализуем URL к виду host/path (поддерживаем https://... и git@host:path)
HOST_PATH="$(printf '%s' "$ORIG_URL" | sed -E 's|^https?://([^@]+@)?||; s|^git@([^:]+):|\1/|')"
TOKEN_URL="https://oauth2:${GIT_TOKEN}@${HOST_PATH}"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
git pull --ff-only "$TOKEN_URL" "$BRANCH"

# Сборка фронта не запускается: на проде нет node/npm, public/build деплоится вручную.
# Composer на хостинге недоступен: vendor/ обновляется вручную, если нужны новые зависимости.

# Чистим bootstrap/cache до любых artisan-команд: иначе закешированный
# packages.php может ссылаться на dev-провайдеры (Pail и пр.), которых нет в vendor/.
echo "==> очистка bootstrap-кеша"
rm -f bootstrap/cache/packages.php \
      bootstrap/cache/services.php \
      bootstrap/cache/config.php \
      bootstrap/cache/routes-v7.php \
      bootstrap/cache/events.php

# Пересобрать список провайдеров пакетов из текущего vendor/.
# || true -- не валим деплой, если discover ругнётся на временное состояние.
echo "==> package:discover"
"$PHP" artisan package:discover --ansi || true

echo "==> миграции"
"$PHP" artisan migrate --force

echo "==> clear + recache"
"$PHP" artisan config:clear
"$PHP" artisan cache:clear
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "==> снятие режима обслуживания"
"$PHP" artisan up

echo "==> deploy OK"

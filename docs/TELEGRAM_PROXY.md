---
name: TELEGRAM_PROXY
description: Как исходящие запросы к Telegram Bot API идут через SOCKS5-прокси и какие инварианты нельзя нарушать (socks5h, multipart для фото, один getUpdates); читать при правках TelegramService и разборе ошибок отправки.
type: project
---
# Telegram через SOCKS5-прокси

## Зачем

С серверов в РФ `api.telegram.org` недоступен (`Could not resolve host` / таймаут). Исходящий трафик к Telegram идёт через SOCKS5-прокси на зарубежном сервере (Shadowsocks). Развёртывание прокси — в [TELEGRAM_PROXY_SETUP.md](./TELEGRAM_PROXY_SETUP.md).

```
Laravel (RU)  ──HTTP──►  ss-local (127.0.0.1:1080)  ──Shadowsocks──►  зарубежный сервер  ──HTTPS──►  api.telegram.org
```

## Как подключено в коде

- Конфиг: `config/services.php`, ключ `services.telegram.proxy`, переменная `TELEGRAM_PROXY`; значение для dev и прода — в `.env.example`.
- Клиент: `TelegramService::http` — единая точка всех HTTP-вызовов `TelegramService`; добавляет `withOptions(['proxy' => ...])`, если значение задано. `http(true)` — multipart-вариант.
- После смены `TELEGRAM_PROXY` в `.env` выполнить `php artisan config:cache`: конфиг читается из кэша.

## Инварианты

- Схема только `socks5h://`. При `socks5://` DNS резолвится локально, `api.telegram.org` не находится. Код передаёт строку как есть, проверки схемы нет.
- Пустое значение `TELEGRAM_PROXY` — прямой запрос (`TelegramService::__construct`). На проде без прокси запросы не пройдут; на dev пусто допустимо.
- Прокси применяется только в `TelegramService::http`. Остальные HTTP-вызовы (Vendista и др.) идут напрямую; общий прокси для них заводить нельзя.
- Фото передаются только локальным путём файла и multipart (`attach://`), не URL: `TelegramService::sendPhoto` (ветка `isLocalFile`) и `TelegramService::sendMediaGroup` (`attach://fileN`, поле `media` — `json_encode`-строка). Путь берётся как `Storage::disk('public')->path(...)`, см. `ServiceVisitController::getPhotoPaths`. Причина: по URL Telegram сам скачивает файл с нашего сервера и нестабильно его достаёт — ошибка `400 ... WEBPAGE_CURL_FAILED`; прокси этому не помогает, он только на исходящий трафик. URL-ветка в `sendPhoto`/`sendMediaGroup` остаётся как запасная, не удалять.
- Ветка URL определяется по `str_starts_with($photo, 'http')`: локальный путь, начинающийся с `http`, уйдёт как URL.
- Один потребитель `getUpdates` (`telegram:poll`). Второй (в том числе `telegram:chat-id` при запущенном `telegram:poll`) даёт 409 Conflict; код его не обрабатывает.
- `getUpdates` запрашивает `allowed_updates=['message']`: `callback_query`, `my_chat_member` и другие типы боту не приходят. `offset` хранится в памяти процесса `telegram:poll`, после рестарта начинается с 0.
- Лимит Telegram — 10 фото в `sendMediaGroup`; код его не проверяет.
- Повторов и обработки 429/`retry_after` нет; любой не-2xx — `Log::error` и `false`/`[]`.

## Типовые ошибки

| Симптом | Причина | Решение |
|---|---|---|
| `Connection refused` | `ss-local` не запущен | `systemctl status shadowsocks-libev-local@config` |
| `Could not resolve host api.telegram.org` | схема `socks5://` вместо `socks5h://` | поменять `TELEGRAM_PROXY` |
| Таймаут через прокси | неверный password/method в конфиге ss-local | перепроверить Outline Access Key |
| Работает из curl, не из Laravel | конфиг в кэше | `php artisan config:cache`; перезапустить `telegram:poll` (долгоживущий процесс, см. [operations.md](./operations.md)); фоновых воркеров очереди нет, FPM подхватывает `.php` по mtime ([DEPLOY.md](./DEPLOY.md)) |
| `Unauthorized` | токен бота недействителен | проверить `TELEGRAM_BOT_TOKEN`, `curl .../getMe` |
| `chat not found` | бот не в чате / неверный `chat_id` (у супергруппы префикс `-100`) | проверить `TELEGRAM_GROUP_CHAT_ID` |
| `400 ... WEBPAGE_CURL_FAILED` | фото ушло URL-ом, а не файлом | передавать локальный путь (см. инварианты) |
| `409 Conflict` на `getUpdates` | запущен второй потребитель | оставить один `telegram:poll` |

Процедура настройки прокси-сервера и его проверки — [TELEGRAM_PROXY_SETUP.md](./TELEGRAM_PROXY_SETUP.md).

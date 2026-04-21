# Telegram через SOCKS5-прокси

## Зачем

Сервер проекта расположен в РФ. С территории РФ домен `api.telegram.org` заблокирован
(`Could not resolve host` / timeout), поэтому прямые запросы из Laravel к Telegram Bot API
не проходят. Решение — пустить трафик через SOCKS5-прокси на зарубежном сервере.

Используется Shadowsocks (Outline), сервер на NL: `103.137.248.69:27503`. Та же инфраструктура,
что и в соседнем проекте **constructor** (saitora.ru) — это эталонная реализация.

## Архитектура

```
Laravel (RU)  ──HTTP──►  ss-local (127.0.0.1:1080)  ──Shadowsocks──►  Outline NL  ──HTTPS──►  api.telegram.org
```

- `ss-local` — клиент Shadowsocks, слушает SOCKS5 на `127.0.0.1:1080`.
- Laravel ходит на `socks5h://127.0.0.1:1080` (схема `socks5h` — DNS резолвится на стороне прокси,
  обязательно для заблокированного домена).
- Прокси применяется **точечно** только к Telegram-клиенту. Остальные HTTP-вызовы
  (Vendista, Яндекс.Карты и т.п.) идут напрямую.

## Реализация в Laravel

### Конфиг — `config/services.php`

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    // ...
    'proxy' => env('TELEGRAM_PROXY'),
],
```

### Клиент — `app/Services/TelegramService.php`

Все HTTP-вызовы (`sendMessage`, `sendPhoto`, `sendMediaGroup`, `getUpdates`) идут через
приватный `http()`, который добавляет `withOptions(['proxy' => ...])` если `TELEGRAM_PROXY`
задан. Если пусто — поведение не меняется (прямой запрос).

```php
private function http(): PendingRequest
{
    $client = Http::asJson();
    if ($this->proxy !== null) {
        $client = $client->withOptions(['proxy' => $this->proxy]);
    }
    return $client;
}
```

### `.env`

```
TELEGRAM_PROXY=socks5h://127.0.0.1:1080
```

На dev — оставить пустым, запросы пойдут напрямую (локально Telegram не блокируется).

## Настройка сервера (native, без docker)

### 1. Получить Outline Access Key

Ключ выдаётся в Outline Manager. Формат: `ss://BASE64==@103.137.248.69:27503/?outline=1`.

Декодировать base64 из части до `@` — получится `method:password`
(например, `chacha20-ietf-poly1305:SECRET_PASS`).

### 2. Установить shadowsocks-libev

```bash
sudo apt update
sudo apt install -y shadowsocks-libev
```

### 3. Конфиг `/etc/shadowsocks-libev/config.json`

```json
{
    "server": "103.137.248.69",
    "server_port": 27503,
    "local_address": "127.0.0.1",
    "local_port": 1080,
    "password": "<PASSWORD_FROM_ACCESS_KEY>",
    "timeout": 300,
    "method": "<METHOD_FROM_ACCESS_KEY>"
}
```

**Не коммитить** конфиг с паролем в git — он хранится только на сервере.

### 4. Запустить systemd-юнит

```bash
sudo systemctl enable --now shadowsocks-libev-local@config
sudo systemctl status shadowsocks-libev-local@config
```

(имя юнита может быть `ss-local@config` в зависимости от дистрибутива)

### 5. Проверка прокси

```bash
curl -s -o /dev/null -w "HTTP %{http_code} in %{time_total}s\n" \
  --max-time 10 --proxy socks5h://127.0.0.1:1080 https://api.telegram.org
```

Ожидание: `HTTP 302 in ~0.2–0.5s`. Таймаут — сервер недоступен или неверный пароль/метод.

### 6. Проверка из Laravel

```bash
php artisan tinker --execute='app(App\Services\TelegramService::class)->sendMessage(config("services.telegram.admin_telegram_id"), "test");'
```

Сообщение должно прийти, в логах не должно быть `Telegram sendMessage failed`.

## Типовые ошибки

| Симптом | Причина | Решение |
|---|---|---|
| `Connection refused` | ss-local не запущен | `systemctl status shadowsocks-libev-local@config` |
| `Could not resolve host api.telegram.org` | Схема `socks5://` вместо `socks5h://` | Поменять в `TELEGRAM_PROXY` |
| Таймаут через прокси | Неверный password/method в config.json | Перепроверить Outline Access Key |
| Работает из curl, не из Laravel | Не перезапущены воркеры очереди | `php artisan queue:restart`, reload php-fpm |
| Telegram возвращает `Unauthorized` | Токен бота недействителен | Проверить `TELEGRAM_BOT_TOKEN`, `curl .../getMe` |
| `chat not found` | Бот не в чате / неверный `chat_id` (у супергруппы префикс `-100`) | Проверить `TELEGRAM_GROUP_CHAT_ID` |

## Отправка фото: только multipart, не URL

### Проблема

При отправке фото визитов через `sendPhoto` / `sendMediaGroup` **по URL**
(когда в `media[i]['media']` лежит публичная ссылка вида
`https://termials.in-site.ru/storage/...`) Telegram-серверы **сами скачивают**
файл с нашего сайта. Сервер в РФ, и встречный HTTP от Telegram-CDN к нам
нестабилен — периодически приходит ошибка:

```
Telegram sendMediaGroup failed
status: 400
body: {"ok":false,"error_code":400,"description":"Bad Request: failed to send message #1 with the error message \"WEBPAGE_CURL_FAILED\""}
```

SOCKS5-прокси решает только исходящий трафик (мы → Telegram), а встречные
запросы от Telegram к нам через него не идут.

### Решение — multipart через `attach://`

Файлы льём мы сами, бинарно, через тот же SOCKS5-прокси. Telegram ничего не
скачивает с нашего сервера → класс ошибок `WEBPAGE_CURL_FAILED` исчезает.

```php
// TelegramService::sendPhoto — локальный путь → multipart
$request = $this->http(true)->attach('photo', fopen($photo, 'r'), basename($photo));
$response = $request->post("{$this->apiBaseUrl}/sendPhoto", $payload);

// TelegramService::sendMediaGroup — attach://fileN + multipart
foreach ($media as $index => &$item) {
    if (!str_starts_with($item['media'], 'http') && is_file($item['media'])) {
        $attachments[] = ['name' => "file{$index}", 'path' => $item['media']];
        $item['media'] = "attach://file{$index}";
    }
}
$request = $this->http(true);
foreach ($attachments as $a) {
    $request = $request->attach($a['name'], fopen($a['path'], 'r'), basename($a['path']));
}
$response = $request->post("{$this->apiBaseUrl}/sendMediaGroup", [
    'chat_id' => $chatId,
    'media' => json_encode($media),
]);
```

`http(true)` возвращает `Http::asMultipart()` вместо `Http::asJson()`,
прокси применяется так же, как для текстовых вызовов.

### Правило для нового кода

Фото в Telegram передаются **абсолютным путём к файлу на диске**
(`Storage::disk('public')->path($photo->path)`), а не публичным URL.
См. `ServiceVisitController::getPhotoPaths()`.

URL-вариант в `sendPhoto` / `sendMediaGroup` оставлен как fallback — не удалять,
но по умолчанию использовать только локальные пути.

## Ссылки

- Полная инструкция по развёртыванию прокси (общая для всех проектов Vigbo):
  `/media/user/H_SSD_31/projects/constructor/docs/telegram-proxy-agent-instructions.md`
- Эталонная реализация прокси: проект **constructor** (saitora.ru),
  `app/Services/Notifications/TelegramNotifier.php`.

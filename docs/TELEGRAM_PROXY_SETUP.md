---
name: TELEGRAM_PROXY_SETUP
description: Процедура настройки сервера SOCKS5-прокси для Telegram (shadowsocks-libev, конфиг, systemd, проверка); читать при развёртывании или восстановлении прокси на новом сервере.
type: reference
---
# Настройка сервера Telegram-прокси

Контракт и инварианты прокси в коде — в [TELEGRAM_PROXY.md](./TELEGRAM_PROXY.md). Значения `<SERVER_IP>` и `<SERVER_PORT>` хранятся на сервере.

## Настройка сервера (native, без docker)

### 1. Получить Outline Access Key

Ключ выдаётся в Outline Manager. Формат: `ss://BASE64==@<SERVER_IP>:<SERVER_PORT>/?outline=1`.

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
    "server": "<SERVER_IP>",
    "server_port": <SERVER_PORT>,
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

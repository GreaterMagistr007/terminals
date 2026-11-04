---
name: counterparts
description: Смежные проекты Terminals — внутренних контрагентов нет, все внешние системы описаны как интеграции
type: project
---
# Контрагенты
Внутренних контрагентов (своих проектов, с которыми Terminals обменивается данными) нет: обращения наружу в `config/services.php` — только Vendista, Telegram и Яндекс Карты.
Внешние системы описаны как интеграции:
- Vendista (терминалы, транзакции, продажи) — `VENDISTA_API.md`
- Telegram Bot API (вход, уведомления в группу, работа через SOCKS5-прокси) — `TELEGRAM_PROXY.md`
- Яндекс Карты (выбор координат точки в админке) — ключ `YANDEX_MAPS_API_KEY` в `.env.example`, использование: `grep -rn ymaps resources/js`

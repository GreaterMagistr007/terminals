---
name: product
description: Назначение Terminals, ключевые сущности предметной области, основные процессы оператора и админа
type: project
---
# Terminals — назначение

## Что это и для кого
PWA для учёта обслуживания кофейных аппаратов. Заменяет записи об обслуживании в Telegram-группе. Пользователи — владелец (admin) и несколько операторов, которые объезжают точки с Android-телефоном. Около 18 точек (со слов владельца; актуальный список — админка `/admin/terminals`), на каждой один аппарат.

## Ключевые сущности предметной области
- **Точка (терминал)** — аппарат в системе Vendista: `VendistaTerminal` (`app/Models/VendistaTerminal.php`) и локальные настройки `TerminalSetting` (короткое имя, адрес, координаты, скрытие, учёт воды, параллельный расход из двух бутылей, склад точки). Отдельных сущностей «точка» и «аппарат» нет: точка и аппарат — одно и то же, связь 1:1 задана терминалом Vendista.
- **Визит обслуживания** — `ServiceVisit`: вода в подключённой и запасной бутылях (доли бутыли, объём — `WaterCheck::BOTTLE_VOLUME_ML`), ингредиенты «принёс / нужно» (`ServiceVisitIngredient`), комментарий, фото (`ServiceVisitPhoto`), геопозиция.
- **Ингредиенты и склады** — `Ingredient`, `IngredientReceipt` (оприходование), `Warehouse`, `WarehouseStock`, `StockMovement`.
- **Продажи** — транзакции Vendista `VendistaTransaction`; по ним считаются налитые и оплаченные стаканы (`CUP_COUNTING.md`).
- **Пользователь** — `User` с ролью `admin` или `operator` (`app/Enums/UserRole.php`).

Поля и связи — в `db_schema.md`.

## Роли
- **admin** — справочники точек, ингредиентов, складов, пользователей, терминалов Vendista; журнал клиентских ошибок (`resources/js/pages/admin/`).
- **operator** — список точек, запись обслуживания, история визитов, продажи (`resources/js/pages/Home.vue`, `Service.vue`, `ServiceHistory.vue`, `Sales.vue`).

## Основные процессы
- **Обслуживание точки.** Список точек (`Home.vue`) → форма визита (`Service.vue`: вода, ингредиенты, комментарий с голосовым вводом через Web Speech API, фото, геолокация) → сохранение. Сохранение идёт offline-first: сначала в IndexedDB, затем на сервер (`OFFLINE_PWA.md`).
- **Уведомления в Telegram.** Визит дублируется сообщением в группу (`ServiceVisitController::buildNotificationText`). Команда `water:check` каждый час присылает уведомление о малом запасе воды (`app/Console/Commands/WaterCheck.php`, расписание в `routes/console.php`).
- **Фото.** Сервер сжимает фото и хранит их только для трёх последних визитов на точку (`ServiceVisitController::compressImage`, `::rotatePhotos`).
- **Вход.** Через Telegram: Login Widget, одноразовая ссылка от бота или код (`app/Services/AuthService.php`: `loginViaWidget`, `loginViaBotToken`, `loginViaCode`). Пользователя заводит админ по инвайт-ссылке (`createInvite`, `activateInvite`). Сессия фактически бессрочная (`DEPLOY.md`, раздел про сессии).

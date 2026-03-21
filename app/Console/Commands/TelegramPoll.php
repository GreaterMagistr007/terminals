<?php

namespace App\Console\Commands;

use App\Models\AuthToken;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Long-polling для Telegram бота (авторизация)';

    private bool $shouldStop = false;

    public function handle(TelegramService $telegramService, AuthService $authService): int
    {
        $this->info('Telegram bot polling запущен. Ctrl+C для остановки.');

        if (function_exists('pcntl')) {
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
            pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
        }

        $offset = 0;

        while (!$this->shouldStop) {
            if (function_exists('pcntl')) {
                pcntl_signal_dispatch();
            }

            $updates = $telegramService->getUpdates($offset);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $this->processUpdate($update, $telegramService, $authService);
            }
        }

        $this->info('Polling остановлен.');

        return self::SUCCESS;
    }

    private function processUpdate(array $update, TelegramService $telegramService, AuthService $authService): void
    {
        $message = $update['message'] ?? null;
        if ($message === null) {
            return;
        }

        $text = $message['text'] ?? '';
        $chatId = (string) $message['chat']['id'];
        $telegramId = (string) $message['from']['id'];
        $firstName = $message['from']['first_name'] ?? '';
        $lastName = $message['from']['last_name'] ?? '';

        // Обработка /start с параметром инвайта
        if (str_starts_with($text, '/start invite_')) {
            $inviteToken = substr($text, strlen('/start invite_'));
            $this->handleInvite($inviteToken, $telegramId, $chatId, $telegramService, $authService);
            return;
        }

        // Обработка /start auth_{token} — авторизация по коду
        if (str_starts_with($text, '/start auth_')) {
            $sessionToken = substr($text, strlen('/start auth_'));
            $username = $message['from']['username'] ?? '';
            $this->handleAuthRequest($sessionToken, $telegramId, $username, $chatId, $telegramService, $authService);
            return;
        }

        // Обработка /start — подсказка
        if ($text === '/start' || $text === '/login') {
            $telegramService->sendMessage($chatId, 'Для входа используйте страницу авторизации приложения.');
            return;
        }

        $telegramService->sendMessage($chatId, 'Для входа используйте страницу авторизации приложения.');
    }

    /** Обработка /start auth_{token} — генерация кода и отправка админу */
    private function handleAuthRequest(
        string $sessionToken,
        string $telegramId,
        string $username,
        string $chatId,
        TelegramService $telegramService,
        AuthService $authService,
    ): void {
        $error = $authService->handleLoginFromBot($sessionToken, $telegramId, $username, $telegramService);

        if ($error !== null) {
            $telegramService->sendMessage($chatId, $error);
            return;
        }

        $telegramService->sendMessage($chatId, 'Код отправлен администратору. Введите его на странице входа.');
        $this->info("Login code sent to admin for telegram: {$telegramId}");
    }

    /** Обработка обычного /start — генерация одноразовой ссылки */
    private function handleLogin(string $telegramId, string $chatId, string $name, TelegramService $telegramService): void
    {
        $user = User::where('telegram_id', $telegramId)->first();

        if ($user === null) {
            $telegramService->sendMessage(
                $chatId,
                'Вы не зарегистрированы в системе. Обратитесь к администратору для получения приглашения.'
            );
            return;
        }

        if (!$user->is_active) {
            $telegramService->sendMessage($chatId, 'Ваш аккаунт деактивирован. Обратитесь к администратору.');
            return;
        }

        $authToken = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, $user->id, $telegramId);
        $appUrl = config('app.url');
        $loginUrl = "{$appUrl}/auth/telegram/{$authToken->token}";

        // Inline-кнопки работают только с HTTPS-ссылками.
        // Для dev (localhost) отправляем ссылку текстом.
        if (str_starts_with($appUrl, 'https://')) {
            $telegramService->sendMessage(
                $chatId,
                "Нажмите кнопку для входа в приложение:",
                [
                    'inline_keyboard' => [
                        [['text' => 'Войти в Terminals', 'url' => $loginUrl]],
                    ],
                ]
            );
        } else {
            $telegramService->sendMessage(
                $chatId,
                "Ссылка для входа (действует 15 минут):\n{$loginUrl}",
                null,
                null
            );
        }

        $this->info("Auth link sent to {$user->name} (telegram: {$telegramId})");
    }

    /** Обработка инвайта — привязка Telegram к пользователю */
    private function handleInvite(string $inviteToken, string $telegramId, string $chatId, TelegramService $telegramService, AuthService $authService): void
    {
        $botAuthToken = $authService->activateInvite($inviteToken, $telegramId);

        if ($botAuthToken === null) {
            $telegramService->sendMessage(
                $chatId,
                'Приглашение недействительно или истекло. Обратитесь к администратору.'
            );
            return;
        }

        $appUrl = config('app.url');
        $loginUrl = "{$appUrl}/auth/telegram/{$botAuthToken->token}";

        if (str_starts_with($appUrl, 'https://')) {
            $telegramService->sendMessage(
                $chatId,
                "Ваш Telegram привязан! Нажмите кнопку для входа:",
                [
                    'inline_keyboard' => [
                        [['text' => 'Войти в Terminals', 'url' => $loginUrl]],
                    ],
                ]
            );
        } else {
            $telegramService->sendMessage(
                $chatId,
                "Ваш Telegram привязан! Ссылка для входа:\n{$loginUrl}",
                null,
                null
            );
        }

        $this->info("Invite activated for telegram: {$telegramId}");
    }
}

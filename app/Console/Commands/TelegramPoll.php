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

        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
            pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
        }

        $offset = 0;

        while (!$this->shouldStop) {
            if (extension_loaded('pcntl')) {
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

        // Обработка /start — обычная авторизация
        if ($text === '/start' || $text === '/login') {
            $this->handleLogin($telegramId, $chatId, "{$firstName} {$lastName}", $telegramService);
            return;
        }

        $telegramService->sendMessage($chatId, 'Используйте /start для авторизации в приложении.');
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

        $telegramService->sendMessage(
            $chatId,
            "Нажмите кнопку для входа в приложение:",
            [
                'inline_keyboard' => [
                    [['text' => 'Войти в Terminals', 'url' => $loginUrl]],
                ],
            ]
        );

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

        $telegramService->sendMessage(
            $chatId,
            "Ваш Telegram привязан! Нажмите кнопку для входа:",
            [
                'inline_keyboard' => [
                    [['text' => 'Войти в Terminals', 'url' => $loginUrl]],
                ],
            ]
        );

        $this->info("Invite activated for telegram: {$telegramId}");
    }
}

<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Одноразовая команда для определения chat_id групп/чатов.
 * Удалить после использования.
 */
class TelegramChatId extends Command
{
    protected $signature = 'telegram:chat-id';
    protected $description = 'Показать chat_id из последних сообщений (для определения ID группы)';

    public function handle(TelegramService $telegramService): int
    {
        $this->info('Получаю последние обновления...');

        $updates = $telegramService->getUpdates(0, 1);

        if (empty($updates)) {
            $this->warn('Нет обновлений. Убедитесь, что:');
            $this->warn('  1. telegram:poll остановлен (он забирает update)');
            $this->warn('  2. Вы написали сообщение в группу после остановки polling');
            return self::FAILURE;
        }

        $this->info('Найдено обновлений: ' . count($updates));
        $this->newLine();

        foreach ($updates as $update) {
            $message = $update['message'] ?? $update['my_chat_member'] ?? null;
            if ($message === null) {
                continue;
            }

            $chat = $message['chat'] ?? [];
            $from = $message['from'] ?? [];

            $this->table(
                ['Параметр', 'Значение'],
                [
                    ['update_id', $update['update_id']],
                    ['chat_id', $chat['id'] ?? '—'],
                    ['chat_type', $chat['type'] ?? '—'],
                    ['chat_title', $chat['title'] ?? '—'],
                    ['from_id', $from['id'] ?? '—'],
                    ['from_name', ($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')],
                    ['text', $message['text'] ?? '(без текста)'],
                ]
            );
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
        {name : Имя администратора}
        {--telegram-id= : Telegram ID пользователя}';

    protected $description = 'Создать администратора';

    public function handle(): int
    {
        $name = $this->argument('name');
        $telegramId = $this->option('telegram-id');

        if ($telegramId !== null) {
            $existing = User::where('telegram_id', $telegramId)->first();
            if ($existing !== null) {
                $this->error("Пользователь с Telegram ID {$telegramId} уже существует: {$existing->name}");
                return self::FAILURE;
            }
        }

        $user = User::create([
            'name' => $name,
            'telegram_id' => $telegramId,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info("Администратор создан: {$user->name} (ID: {$user->id})");

        if ($telegramId === null) {
            $this->warn('Telegram ID не указан. Для авторизации нужно привязать Telegram.');
        }

        return self::SUCCESS;
    }
}

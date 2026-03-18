<?php

namespace Tests\Unit\Stage1;

use App\Services\TelegramService;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    /** verifyWidgetData с правильным hash возвращает true */
    public function test_verify_widget_data_with_correct_hash_returns_true(): void
    {
        // Устанавливаем известный bot_token для теста
        $botToken = 'test_bot_token_12345';
        config(['services.telegram.bot_token' => $botToken]);

        $service = new TelegramService();

        $data = [
            'id' => '123456',
            'first_name' => 'Test',
            'auth_date' => (string) time(),
        ];

        // Вычисляем правильный hash по алгоритму Telegram
        $checkString = collect($data)
            ->sortKeys()
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $checkString, $secretKey);

        $data['hash'] = $hash;

        $this->assertTrue($service->verifyWidgetData($data));
    }

    /** verifyWidgetData с неправильным hash возвращает false */
    public function test_verify_widget_data_with_wrong_hash_returns_false(): void
    {
        $botToken = 'test_bot_token_12345';
        config(['services.telegram.bot_token' => $botToken]);

        $service = new TelegramService();

        $data = [
            'id' => '123456',
            'first_name' => 'Test',
            'auth_date' => '1234567890',
            'hash' => 'completely_wrong_hash_value',
        ];

        $this->assertFalse($service->verifyWidgetData($data));
    }

    /** verifyWidgetData без hash возвращает false */
    public function test_verify_widget_data_without_hash_returns_false(): void
    {
        $botToken = 'test_bot_token_12345';
        config(['services.telegram.bot_token' => $botToken]);

        $service = new TelegramService();

        $data = [
            'id' => '123456',
            'first_name' => 'Test',
            'auth_date' => '1234567890',
        ];

        $this->assertFalse($service->verifyWidgetData($data));
    }
}

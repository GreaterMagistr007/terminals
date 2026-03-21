<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /** Максимальный возраст auth_date (в секундах) для защиты от replay attack */
    private const AUTH_DATE_TTL = 300;

    private string $botToken;
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiBaseUrl = 'https://api.telegram.org/bot' . $this->botToken;
    }

    /**
     * Верификация данных от Telegram Login Widget.
     * https://core.telegram.org/widgets/login#checking-authorization
     */
    public function verifyWidgetData(array $data): bool
    {
        if (empty($data['hash'])) {
            return false;
        }

        // Защита от replay attack: проверка свежести auth_date
        if (empty($data['auth_date']) || (time() - (int) $data['auth_date']) > self::AUTH_DATE_TTL) {
            return false;
        }

        $hash = $data['hash'];
        $checkData = collect($data)
            ->except('hash')
            ->sortKeys()
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash('sha256', $this->botToken, true);
        $calculatedHash = hash_hmac('sha256', $checkData, $secretKey);

        return hash_equals($calculatedHash, $hash);
    }

    /** Отправка сообщения пользователю через бота */
    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = 'HTML'): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::post("{$this->apiBaseUrl}/sendMessage", $payload);

        if (!$response->successful()) {
            Log::error('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /** Отправка фото по URL */
    public function sendPhoto(string $chatId, string $photoUrl, ?string $caption = null, ?string $parseMode = 'HTML'): bool
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        $response = Http::post("{$this->apiBaseUrl}/sendPhoto", $payload);

        if (!$response->successful()) {
            Log::error('Telegram sendPhoto failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Отправка группы медиафайлов (до 10 фото).
     *
     * @param string $chatId
     * @param array $media Массив InputMediaPhoto: [['type' => 'photo', 'media' => 'url', 'caption' => '...', 'parse_mode' => 'HTML'], ...]
     */
    public function sendMediaGroup(string $chatId, array $media): bool
    {
        $response = Http::post("{$this->apiBaseUrl}/sendMediaGroup", [
            'chat_id' => $chatId,
            'media' => json_encode($media),
        ]);

        if (!$response->successful()) {
            Log::error('Telegram sendMediaGroup failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /** Получение обновлений через long-polling */
    public function getUpdates(int $offset = 0, int $timeout = 30): array
    {
        $response = Http::timeout($timeout + 5)->post("{$this->apiBaseUrl}/getUpdates", [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ]);

        if (!$response->successful()) {
            Log::error('Telegram getUpdates failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json('result', []);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /** Максимальный возраст auth_date (в секундах) для защиты от replay attack */
    private const AUTH_DATE_TTL = 300;

    private string $botToken;
    private string $apiBaseUrl;
    private ?string $proxy;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiBaseUrl = 'https://api.telegram.org/bot' . $this->botToken;
        $this->proxy = config('services.telegram.proxy') ?: null;
    }

    /**
     * HTTP-клиент для вызовов Telegram Bot API.
     * Если задан TELEGRAM_PROXY — трафик идёт через SOCKS5 (обход блокировки в РФ).
     *
     * @param bool $multipart true — для отправки файлов (sendPhoto/sendMediaGroup с локальными путями).
     */
    private function http(bool $multipart = false): PendingRequest
    {
        $client = $multipart ? Http::asMultipart() : Http::asJson();

        if ($this->proxy !== null) {
            $client = $client->withOptions(['proxy' => $this->proxy]);
        }

        return $client;
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

        $response = $this->http()->post("{$this->apiBaseUrl}/sendMessage", $payload);

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

    /**
     * Отправка фото.
     *
     * @param string $photo Локальный путь к файлу (отправляется multipart — надёжнее)
     *                      или публичный URL (Telegram сам скачает; может падать WEBPAGE_CURL_FAILED).
     */
    public function sendPhoto(string $chatId, string $photo, ?string $caption = null, ?string $parseMode = 'HTML'): bool
    {
        $isLocalFile = !str_starts_with($photo, 'http') && is_file($photo);

        $payload = ['chat_id' => $chatId];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($isLocalFile) {
            $request = $this->http(true)->attach('photo', fopen($photo, 'r'), basename($photo));
            $response = $request->post("{$this->apiBaseUrl}/sendPhoto", $payload);
        } else {
            $payload['photo'] = $photo;
            $response = $this->http()->post("{$this->apiBaseUrl}/sendPhoto", $payload);
        }

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
     * В `media` поле `media` может быть:
     *  - локальный путь к файлу → будет отправлен multipart через attach:// (надёжно, рекомендуется);
     *  - публичный URL → Telegram скачает сам (может падать WEBPAGE_CURL_FAILED, если нас не видно из внешки).
     *
     * @param array $media Массив InputMediaPhoto:
     *                     [['type' => 'photo', 'media' => '<path|url>', 'caption' => '...', 'parse_mode' => 'HTML'], ...]
     */
    public function sendMediaGroup(string $chatId, array $media): bool
    {
        $attachments = [];
        foreach ($media as $index => &$item) {
            $src = $item['media'] ?? null;
            if ($src !== null && !str_starts_with($src, 'http') && is_file($src)) {
                $name = "file{$index}";
                $attachments[] = [
                    'name' => $name,
                    'path' => $src,
                ];
                $item['media'] = "attach://{$name}";
            }
        }
        unset($item);

        $payload = [
            'chat_id' => $chatId,
            'media' => json_encode($media),
        ];

        if (!empty($attachments)) {
            $request = $this->http(true);
            foreach ($attachments as $a) {
                $request = $request->attach($a['name'], fopen($a['path'], 'r'), basename($a['path']));
            }
            $response = $request->post("{$this->apiBaseUrl}/sendMediaGroup", $payload);
        } else {
            $response = $this->http()->post("{$this->apiBaseUrl}/sendMediaGroup", $payload);
        }

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
        $response = $this->http()->timeout($timeout + 5)->post("{$this->apiBaseUrl}/getUpdates", [
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

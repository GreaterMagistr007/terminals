<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuthToken extends Model
{
    use MassPrunable;
    public const TYPE_BOT_AUTH = 'bot_auth';
    public const TYPE_INVITE = 'invite';
    public const TYPE_LOGIN_SESSION = 'login_session';
    public const TYPE_LOGIN_CODE = 'login_code';

    /** Время жизни токена в минутах */
    public const TTL_MINUTES = 15;

    /** Время жизни кода авторизации в минутах */
    public const CODE_TTL_MINUTES = 5;

    protected $fillable = [
        'token',
        'type',
        'user_id',
        'telegram_id',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Создание нового токена */
    public static function generate(string $type, ?int $userId = null, ?string $telegramId = null): self
    {
        return self::create([
            'token' => Str::random(64),
            'type' => $type,
            'user_id' => $userId,
            'telegram_id' => $telegramId,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);
    }

    /** Создание сессии для страницы логина (токен для ссылки на бота) */
    public static function generateLoginSession(): self
    {
        return self::create([
            'token' => Str::random(32),
            'type' => self::TYPE_LOGIN_SESSION,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);
    }

    /** Создание 6-значного кода авторизации */
    public static function generateLoginCode(int $userId): self
    {
        // Инвалидация предыдущих неиспользованных кодов пользователя
        self::where('type', self::TYPE_LOGIN_CODE)
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        return self::create([
            'token' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'type' => self::TYPE_LOGIN_CODE,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);
    }

    /** Токен валиден (не использован и не истёк) */
    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /** Пометить токен как использованный */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /** Удаление истёкших и использованных токенов (model:prune) */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now())
            ->orWhereNotNull('used_at');
    }
}

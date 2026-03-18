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

    /** Время жизни токена в минутах */
    public const TTL_MINUTES = 15;

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

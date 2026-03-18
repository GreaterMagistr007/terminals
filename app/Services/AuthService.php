<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService
{
    /**
     * Авторизация через Telegram Login Widget.
     * Верифицирует данные, находит/создаёт пользователя, авторизует.
     */
    public function loginViaWidget(array $telegramData, TelegramService $telegramService): ?User
    {
        if (!$telegramService->verifyWidgetData($telegramData)) {
            return null;
        }

        $user = $this->findOrCreateUserByTelegramId(
            (string) $telegramData['id'],
            $telegramData['first_name'] . ' ' . ($telegramData['last_name'] ?? ''),
        );

        if ($user === null || !$user->is_active) {
            return $user;
        }

        $this->authenticate($user);

        return $user;
    }

    /**
     * Авторизация через одноразовый токен бота.
     * Токен создаётся ботом при /start, пользователь переходит по ссылке.
     */
    public function loginViaBotToken(string $token): ?User
    {
        return DB::transaction(function () use ($token): ?User {
            $authToken = AuthToken::where('token', $token)
                ->where('type', AuthToken::TYPE_BOT_AUTH)
                ->lockForUpdate()
                ->first();

            if ($authToken === null || !$authToken->isValid()) {
                return null;
            }

            $authToken->markAsUsed();

            $user = $this->findOrCreateUserByTelegramId(
                $authToken->telegram_id,
                'User ' . $authToken->telegram_id,
            );

            if ($user === null || !$user->is_active) {
                return $user;
            }

            $this->authenticate($user);

            return $user;
        });
    }

    /**
     * Активация инвайта: привязка telegram_id к пользователю.
     * Возвращает токен для авторизации.
     */
    public function activateInvite(string $inviteToken, string $telegramId): ?AuthToken
    {
        return DB::transaction(function () use ($inviteToken, $telegramId): ?AuthToken {
            $invite = AuthToken::where('token', $inviteToken)
                ->where('type', AuthToken::TYPE_INVITE)
                ->lockForUpdate()
                ->first();

            if ($invite === null || !$invite->isValid() || $invite->user_id === null) {
                return null;
            }

            $user = $invite->user;

            // Проверка: telegram_id уже привязан к другому пользователю
            $existingUser = User::where('telegram_id', $telegramId)->first();
            if ($existingUser !== null && $existingUser->id !== $user->id) {
                return null;
            }

            $user->update(['telegram_id' => $telegramId]);
            $invite->markAsUsed();

            return AuthToken::generate(AuthToken::TYPE_BOT_AUTH, $user->id, $telegramId);
        });
    }

    /** Создание инвайт-токена для пользователя (вызывается админом) */
    public function createInvite(User $user): AuthToken
    {
        return AuthToken::generate(AuthToken::TYPE_INVITE, $user->id);
    }

    /** Найти существующего или создать нового пользователя по telegram_id */
    private function findOrCreateUserByTelegramId(string $telegramId, string $fallbackName): ?User
    {
        $user = User::where('telegram_id', $telegramId)->first();

        if ($user !== null) {
            return $user;
        }

        // Новый пользователь через Telegram — создаём как неактивного (ждёт одобрения админа)
        return User::create([
            'name' => trim($fallbackName),
            'telegram_id' => $telegramId,
            'role' => UserRole::Operator,
            'is_active' => false,
        ]);
    }

    /** Авторизация пользователя в сессии */
    private function authenticate(User $user): void
    {
        Auth::login($user, true);
    }
}

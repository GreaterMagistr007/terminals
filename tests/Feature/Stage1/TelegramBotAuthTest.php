<?php

namespace Tests\Feature\Stage1;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramBotAuthTest extends TestCase
{
    use RefreshDatabase;

    /** POST /api/auth/telegram-bot/{valid_token} с валидным токеном возвращает 200 и user */
    public function test_bot_auth_with_valid_token_returns_200(): void
    {
        $user = User::factory()->create(['telegram_id' => '123456789']);

        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            $user->id,
            '123456789',
        );

        $response = $this->postJson("/api/auth/telegram-bot/{$authToken->token}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['id', 'name']]);
        $response->assertJsonPath('user.id', $user->id);
    }

    /** POST /api/auth/telegram-bot/{expired_token} с истёкшим токеном возвращает 401 */
    public function test_bot_auth_with_expired_token_returns_401(): void
    {
        $user = User::factory()->create(['telegram_id' => '123456789']);

        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            $user->id,
            '123456789',
        );

        // Устанавливаем expires_at в прошлое
        $authToken->update(['expires_at' => now()->subMinutes(1)]);

        $response = $this->postJson("/api/auth/telegram-bot/{$authToken->token}");

        $response->assertStatus(401);
    }

    /** POST /api/auth/telegram-bot/{used_token} с использованным токеном возвращает 401 */
    public function test_bot_auth_with_used_token_returns_401(): void
    {
        $user = User::factory()->create(['telegram_id' => '123456789']);

        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            $user->id,
            '123456789',
        );

        $authToken->markAsUsed();

        $response = $this->postJson("/api/auth/telegram-bot/{$authToken->token}");

        $response->assertStatus(401);
    }

    /** POST /api/auth/telegram-bot/{invalid_token} с несуществующим токеном возвращает 401 */
    public function test_bot_auth_with_invalid_token_returns_401(): void
    {
        $response = $this->postJson('/api/auth/telegram-bot/nonexistent_token_value');

        $response->assertStatus(401);
    }

    /** POST /api/auth/telegram-bot/{token} для деактивированного пользователя возвращает 403 */
    public function test_bot_auth_with_inactive_user_returns_403(): void
    {
        // Существующий деактивированный пользователь
        $user = User::factory()->inactive()->create(['telegram_id' => '999888777']);

        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            $user->id,
            '999888777',
        );

        $response = $this->postJson("/api/auth/telegram-bot/{$authToken->token}");

        // Сервис возвращает неактивного пользователя → контроллер отдаёт 403
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Аккаунт ожидает активации администратором.']);
    }

    /** После авторизации по токену бота с новым telegram_id создаётся пользователь с is_active=false */
    public function test_bot_auth_creates_new_inactive_user_for_new_telegram_id(): void
    {
        $telegramId = '777666555';

        // Токен с telegram_id, которого нет в базе
        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            null,
            $telegramId,
        );

        $response = $this->postJson("/api/auth/telegram-bot/{$authToken->token}");

        // Новый пользователь создан с is_active=false, контроллер проверяет и возвращает 403
        $response->assertStatus(403);

        // Проверяем, что пользователь создан в базе
        $this->assertDatabaseHas('users', [
            'telegram_id' => $telegramId,
            'is_active' => false,
        ]);
    }
}

<?php

namespace Tests\Unit\Stage1;

use App\Models\AuthToken;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /** loginViaBotToken с валидным токеном возвращает User */
    public function test_login_via_bot_token_with_valid_token_returns_user(): void
    {
        $user = User::factory()->create(['telegram_id' => '111222333']);

        $authToken = AuthToken::generate(
            AuthToken::TYPE_BOT_AUTH,
            $user->id,
            '111222333',
        );

        $result = $this->authService->loginViaBotToken($authToken->token);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    /** loginViaBotToken с невалидным токеном возвращает null */
    public function test_login_via_bot_token_with_invalid_token_returns_null(): void
    {
        $result = $this->authService->loginViaBotToken('nonexistent_token');

        $this->assertNull($result);
    }

    /** createInvite создаёт токен типа invite */
    public function test_create_invite_creates_invite_token(): void
    {
        $user = User::factory()->create();

        $token = $this->authService->createInvite($user);

        $this->assertInstanceOf(AuthToken::class, $token);
        $this->assertSame(AuthToken::TYPE_INVITE, $token->type);
        $this->assertEquals($user->id, $token->user_id);
        $this->assertTrue($token->isValid());
    }

    /** activateInvite привязывает telegram_id к пользователю */
    public function test_activate_invite_binds_telegram_id_to_user(): void
    {
        $user = User::factory()->withoutTelegram()->create();
        $invite = $this->authService->createInvite($user);

        $telegramId = '444555666';
        $botToken = $this->authService->activateInvite($invite->token, $telegramId);

        $this->assertNotNull($botToken);
        $this->assertSame(AuthToken::TYPE_BOT_AUTH, $botToken->type);

        // Проверяем, что telegram_id привязан к пользователю
        $this->assertEquals($telegramId, $user->fresh()->telegram_id);

        // Инвайт помечен как использованный
        $this->assertNotNull($invite->fresh()->used_at);
    }
}

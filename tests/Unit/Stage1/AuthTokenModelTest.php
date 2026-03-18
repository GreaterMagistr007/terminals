<?php

namespace Tests\Unit\Stage1;

use App\Models\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenModelTest extends TestCase
{
    use RefreshDatabase;

    /** AuthToken::generate() создаёт токен нужного типа */
    public function test_generate_creates_token_with_correct_type(): void
    {
        $user = \App\Models\User::factory()->create();

        $botToken = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, null, '123456');
        $inviteToken = AuthToken::generate(AuthToken::TYPE_INVITE, $user->id);

        $this->assertSame(AuthToken::TYPE_BOT_AUTH, $botToken->type);
        $this->assertSame(AuthToken::TYPE_INVITE, $inviteToken->type);
        $this->assertNotEmpty($botToken->token);
        $this->assertEquals(64, strlen($botToken->token));
    }

    /** AuthToken::isValid() возвращает true для нового токена */
    public function test_is_valid_returns_true_for_new_token(): void
    {
        $token = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, null, '123456');

        $this->assertTrue($token->isValid());
    }

    /** AuthToken::isValid() возвращает false для истёкшего токена */
    public function test_is_valid_returns_false_for_expired_token(): void
    {
        $token = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, null, '123456');
        $token->update(['expires_at' => now()->subMinutes(1)]);

        $this->assertFalse($token->fresh()->isValid());
    }

    /** AuthToken::isValid() возвращает false для использованного токена */
    public function test_is_valid_returns_false_for_used_token(): void
    {
        $token = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, null, '123456');
        $token->markAsUsed();

        $this->assertFalse($token->fresh()->isValid());
    }

    /** AuthToken::markAsUsed() заполняет поле used_at */
    public function test_mark_as_used_fills_used_at(): void
    {
        $token = AuthToken::generate(AuthToken::TYPE_BOT_AUTH, null, '123456');

        $this->assertNull($token->used_at);

        $token->markAsUsed();

        $this->assertNotNull($token->fresh()->used_at);
    }
}

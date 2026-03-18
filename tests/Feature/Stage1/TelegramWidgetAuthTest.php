<?php

namespace Tests\Feature\Stage1;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramWidgetAuthTest extends TestCase
{
    use RefreshDatabase;

    /** POST /api/auth/telegram-widget с валидным hash возвращает 200 */
    public function test_widget_auth_with_valid_hash_returns_200(): void
    {
        // Создаём активного пользователя с telegram_id
        $user = User::factory()->create(['telegram_id' => '100200300']);

        // Мокаем TelegramService, чтобы verifyWidgetData вернул true
        $mock = Mockery::mock(TelegramService::class);
        $mock->shouldReceive('verifyWidgetData')->once()->andReturn(true);
        $this->app->instance(TelegramService::class, $mock);

        $response = $this->postJson('/api/auth/telegram-widget', [
            'id' => '100200300',
            'first_name' => 'Test',
            'auth_date' => time(),
            'hash' => 'fakehash',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user']);
    }

    /** POST /api/auth/telegram-widget с невалидным hash возвращает 401 */
    public function test_widget_auth_with_invalid_hash_returns_401(): void
    {
        $mock = Mockery::mock(TelegramService::class);
        $mock->shouldReceive('verifyWidgetData')->once()->andReturn(false);
        $this->app->instance(TelegramService::class, $mock);

        $response = $this->postJson('/api/auth/telegram-widget', [
            'id' => '100200300',
            'first_name' => 'Test',
            'auth_date' => time(),
            'hash' => 'invalid_hash',
        ]);

        $response->assertStatus(401);
    }

    /** POST /api/auth/telegram-widget без обязательных полей возвращает 422 */
    public function test_widget_auth_without_required_fields_returns_422(): void
    {
        $response = $this->postJson('/api/auth/telegram-widget', []);

        $response->assertStatus(422);
    }
}

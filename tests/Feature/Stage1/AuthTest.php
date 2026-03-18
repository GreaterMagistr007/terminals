<?php

namespace Tests\Feature\Stage1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** GET /api/auth/me без авторизации возвращает 401 */
    public function test_me_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** GET /api/auth/me с авторизованным пользователем возвращает 200 и JSON с user */
    public function test_me_with_authenticated_user_returns_200(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['id', 'name', 'role', 'is_active']]);
        $response->assertJsonPath('user.id', $user->id);
    }

    /** POST /api/auth/logout — выход и корректный ответ */
    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        // Авторизованный запрос на logout возвращает 200
        $logoutResponse = $this->actingAs($user)
            ->withHeaders(['Referer' => 'http://localhost'])
            ->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJson(['message' => 'Вы вышли из системы.']);

        // Проверяем, что web guard сбросил авторизацию
        $this->assertFalse(auth()->guard('web')->check());
    }

    /** GET /api/auth/me с деактивированным пользователем возвращает 403 */
    public function test_me_with_inactive_user_returns_403(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertStatus(403);
    }
}

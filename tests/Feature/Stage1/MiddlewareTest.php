<?php

namespace Tests\Feature\Stage1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** Защищённый маршрут с auth:sanctum без авторизации возвращает 401 */
    public function test_protected_route_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** Защищённый маршрут с active middleware для деактивированного пользователя возвращает 403 */
    public function test_active_middleware_blocks_inactive_user_with_403(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertStatus(403);
    }

    /** Защищённый маршрут с admin middleware для оператора возвращает 403 */
    public function test_admin_middleware_blocks_operator_with_403(): void
    {
        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->getJson('/api/admin/users');

        $response->assertStatus(403);
    }
}

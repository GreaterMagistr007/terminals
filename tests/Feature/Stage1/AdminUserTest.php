<?php

namespace Tests\Feature\Stage1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /** GET /api/admin/users без авторизации возвращает 401 */
    public function test_list_users_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401);
    }

    /** GET /api/admin/users как оператор возвращает 403 */
    public function test_list_users_as_operator_returns_403(): void
    {
        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    /** GET /api/admin/users как админ возвращает 200 с JSON users */
    public function test_list_users_as_admin_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/users');

        $response->assertStatus(200);
        $response->assertJsonStructure(['users']);
        // Админ + 3 оператора = 4
        $response->assertJsonCount(4, 'users');
    }

    /** POST /api/admin/users как админ создаёт пользователя → 201, user + invite_url */
    public function test_create_user_as_admin_returns_201(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'Новый оператор',
            'role' => 'operator',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['user' => ['id', 'name', 'role'], 'invite_url']);
        $response->assertJsonPath('user.name', 'Новый оператор');
        $response->assertJsonPath('user.role', 'operator');
    }

    /** POST /api/admin/users с невалидными данными возвращает 422 */
    public function test_create_user_with_invalid_data_returns_422(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => '',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422);
    }

    /** PUT /api/admin/users/{id} — смена роли возвращает 200, роль обновлена */
    public function test_update_user_role_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/users/{$operator->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'id' => $operator->id,
            'role' => 'admin',
        ]);
    }

    /** PUT /api/admin/users/{id} — деактивация возвращает 200, is_active=false */
    public function test_deactivate_user_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/users/{$operator->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $operator->id,
            'is_active' => false,
        ]);
    }

    /** POST /api/admin/users/{id}/invite — генерация инвайта возвращает 200 с invite_url */
    public function test_generate_invite_returns_200(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->withoutTelegram()->create();

        $response = $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/invite");

        $response->assertStatus(200);
        $response->assertJsonStructure(['invite_url']);
    }

    /** POST /api/admin/users/{id}/invite для пользователя с привязанным Telegram возвращает 422 */
    public function test_generate_invite_for_user_with_telegram_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(); // с telegram_id по умолчанию

        $response = $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/invite");

        $response->assertStatus(422);
    }
}

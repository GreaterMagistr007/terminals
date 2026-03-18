<?php

namespace Tests\Unit\Stage1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    /** Команда app:create-admin создаёт пользователя с ролью admin */
    public function test_create_admin_command_creates_admin_user(): void
    {
        $this->artisan('app:create-admin', ['name' => 'Test Admin'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Test Admin',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /** Команда app:create-admin с --telegram-id создаёт пользователя с привязанным telegram */
    public function test_create_admin_command_with_telegram_id(): void
    {
        $this->artisan('app:create-admin', [
            'name' => 'Telegram Admin',
            '--telegram-id' => '987654321',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Telegram Admin',
            'telegram_id' => '987654321',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /** Команда app:create-admin с дублирующим telegram-id завершается ошибкой */
    public function test_create_admin_command_with_duplicate_telegram_id_fails(): void
    {
        User::factory()->create(['telegram_id' => '111111111']);

        $this->artisan('app:create-admin', [
            'name' => 'Duplicate Admin',
            '--telegram-id' => '111111111',
        ])->assertExitCode(1);

        // Пользователь не создан
        $this->assertDatabaseMissing('users', [
            'name' => 'Duplicate Admin',
        ]);
    }
}

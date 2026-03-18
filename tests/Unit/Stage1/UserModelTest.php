<?php

namespace Tests\Unit\Stage1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /** User::isAdmin() возвращает true для пользователя с ролью admin */
    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
    }

    /** User::isAdmin() возвращает false для пользователя с ролью operator */
    public function test_is_admin_returns_false_for_operator(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->isAdmin());
    }

    /** User::isOperator() возвращает true для пользователя с ролью operator */
    public function test_is_operator_returns_true_for_operator(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isOperator());
    }

    /** UserRole enum содержит значения admin и operator */
    public function test_user_role_enum_values(): void
    {
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('operator', UserRole::Operator->value);
    }
}

<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'telegram_id' => (string) fake()->unique()->randomNumber(9),
            'role' => UserRole::Operator,
            'is_active' => true,
        ];
    }

    /** Администратор */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin,
        ]);
    }

    /** Деактивированный пользователь */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /** Без привязки Telegram */
    public function withoutTelegram(): static
    {
        return $this->state(fn () => [
            'telegram_id' => null,
        ]);
    }
}

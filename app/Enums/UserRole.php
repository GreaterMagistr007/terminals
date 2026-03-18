<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';

    /** Человекочитаемое название роли */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Operator => 'Оператор',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'cost_per_unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_unit' => 'float',
            'is_active' => 'boolean',
        ];
    }
}

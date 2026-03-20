<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientReceipt extends Model
{
    protected $fillable = [
        'warehouse_id',
        'ingredient_id',
        'quantity_units',
        'cost_per_unit',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity_units' => 'integer',
            'cost_per_unit' => 'float',
        ];
    }

    /** Склад */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Ингредиент */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

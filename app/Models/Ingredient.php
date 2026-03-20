<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'unit',
        'cost_per_unit',
        'quantity_per_package',
        'quantity_per_box',
        'cost_per_unit_in_box',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_unit' => 'float',
            'quantity_per_package' => 'float',
            'quantity_per_box' => 'integer',
            'cost_per_unit_in_box' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** Остатки на складах */
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /** История оприходований */
    public function receipts(): HasMany
    {
        return $this->hasMany(IngredientReceipt::class);
    }

    /** История движений товара */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}

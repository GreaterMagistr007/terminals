<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /** Остатки на складе */
    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /** История оприходований */
    public function receipts(): HasMany
    {
        return $this->hasMany(IngredientReceipt::class);
    }
}

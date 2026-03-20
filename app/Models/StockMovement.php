<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_WRITE_OFF = 'write_off';

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'type',
        'quantity',
        'from_warehouse_id',
        'to_warehouse_id',
        'cost_per_unit',
        'source',
        'reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost_per_unit' => 'float',
        ];
    }

    /** Ингредиент */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** Пользователь, выполнивший операцию */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Склад-источник */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /** Склад-получатель */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}

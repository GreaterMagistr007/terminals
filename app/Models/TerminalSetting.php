<?php

namespace App\Models;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminalSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vendista_terminal_id',
        'short_name',
        'hidden',
        'uses_water',
        'address',
        'latitude',
        'longitude',
        'warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'hidden' => 'boolean',
            'uses_water' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** Терминал Vendista */
    public function vendistaTerminal(): BelongsTo
    {
        return $this->belongsTo(VendistaTerminal::class);
    }

    /** Склад отгрузки */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

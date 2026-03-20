<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendistaTerminal extends Model
{
    protected $fillable = [
        'vendista_id',
        'comment',
        'vendista_machine_id',
        'tid',
        'serial_number',
        'latitude',
        'longitude',
        'last_online_at',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'vendista_id' => 'integer',
            'vendista_machine_id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_online_at' => 'datetime',
            'state' => 'integer',
        ];
    }

    /** Настройки точки */
    public function settings(): HasOne
    {
        return $this->hasOne(TerminalSetting::class);
    }

    /** Используемые ингредиенты (в порядке sort_order) */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'terminal_ingredients')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}

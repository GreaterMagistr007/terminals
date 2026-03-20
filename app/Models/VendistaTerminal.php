<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendistaTerminal extends Model
{
    public $timestamps = false;

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
            'service_visits_max_visited_at' => 'datetime',
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

    /** Все визиты обслуживания */
    public function serviceVisits(): HasMany
    {
        return $this->hasMany(ServiceVisit::class, 'terminal_id');
    }

    /** Последний визит обслуживания */
    public function latestVisit(): HasOne
    {
        return $this->hasOne(ServiceVisit::class, 'terminal_id')->latestOfMany('visited_at');
    }

    /** Транзакции Vendista */
    public function transactions(): HasMany
    {
        return $this->hasMany(VendistaTransaction::class, 'term_id', 'vendista_id');
    }
}

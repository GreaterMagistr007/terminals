<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendistaTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trans_id',
        'term_id',
        'sum',
        'time',
        'result',
        'status',
        'response_code',
        'card_number',
        'reverse_id',
        'reverse_time',
    ];

    protected function casts(): array
    {
        return [
            'trans_id' => 'integer',
            'term_id' => 'integer',
            'sum' => 'integer',
            'time' => 'datetime',
            'result' => 'integer',
            'status' => 'integer',
            'response_code' => 'integer',
            'reverse_id' => 'integer',
            'reverse_time' => 'datetime',
        ];
    }

    /** Терминал Vendista */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(VendistaTerminal::class, 'term_id', 'vendista_id');
    }

    // Единое определение «стакан = успешная продажа без возврата».
    // reverse_id != 0 у обеих записей пары (оригинал и возвратная) — фильтр отсекает обе.
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('result', 1)->where('reverse_id', 0);
    }

    public function scopeForTerminal(Builder $query, int $vendistaId): Builder
    {
        return $query->where('term_id', $vendistaId);
    }

    // Строго после момента (для подсчёта продаж после визита: транзакция в момент визита уже учтена в его остатках).
    public function scopeAfter(Builder $query, DateTimeInterface $time): Builder
    {
        return $query->where('time', '>', $time);
    }

    // Включительно с момента (для подсчёта от начала суток).
    public function scopeSince(Builder $query, DateTimeInterface $time): Builder
    {
        return $query->where('time', '>=', $time);
    }
}

<?php

namespace App\Models;

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminalSetting extends Model
{
    protected $fillable = [
        'vendista_terminal_id',
        'hidden',
        'uses_water',
        'address',
        'latitude',
        'longitude',
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
}

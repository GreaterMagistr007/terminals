<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}

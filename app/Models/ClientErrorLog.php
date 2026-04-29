<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientErrorLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'source',
        'message',
        'context',
        'url',
        'user_agent',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

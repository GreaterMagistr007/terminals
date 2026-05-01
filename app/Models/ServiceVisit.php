<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceVisit extends Model
{
    protected $fillable = [
        'terminal_id',
        'user_id',
        'client_uuid',
        'visited_at',
        'water_main',
        'water_spare',
        'comment',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'water_main' => 'float',
            'water_spare' => 'float',
        ];
    }

    /** Терминал (точка) */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(VendistaTerminal::class, 'terminal_id');
    }

    /** Оператор, выполнивший обслуживание */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ингредиенты визита */
    public function ingredients(): HasMany
    {
        return $this->hasMany(ServiceVisitIngredient::class);
    }

    /** Фотографии визита */
    public function photos(): HasMany
    {
        return $this->hasMany(ServiceVisitPhoto::class);
    }
}

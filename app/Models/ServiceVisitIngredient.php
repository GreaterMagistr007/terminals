<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceVisitIngredient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_visit_id',
        'ingredient_id',
        'brought',
        'needed',
    ];

    protected function casts(): array
    {
        return [
            'brought' => 'integer',
            'needed' => 'integer',
        ];
    }

    /** Визит обслуживания */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(ServiceVisit::class, 'service_visit_id');
    }

    /** Ингредиент */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

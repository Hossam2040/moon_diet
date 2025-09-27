<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_variant_id',
        'duration_type',
        'meals_per_day',
        'price',
    ];

    protected $casts = [
        'meals_per_day' => 'integer',
        'price' => 'decimal:2',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MealPlanVariant::class, 'meal_plan_variant_id');
    }
}



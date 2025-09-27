<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPlanVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'grams',
        'active',
    ];

    protected $casts = [
        'grams' => 'integer',
        'active' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class, 'meal_plan_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MealPlanPrice::class, 'meal_plan_variant_id');
    }
}



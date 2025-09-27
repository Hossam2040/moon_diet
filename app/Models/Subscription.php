<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'meal_plan_id', 'start_date', 'end_date', 'status', 'total_paid', 'payment_method', 'next_renewal_at', 'meal_plan_variant_id', 'duration_type', 'meals_per_day', 'total_meals'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_paid' => 'decimal:2',
        'next_renewal_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class, 'meal_plan_id');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(SubscriptionMeal::class);
    }
}



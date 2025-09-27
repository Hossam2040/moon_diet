<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en',
        'description_ar', 'description_en',
        'image_url',
        'duration_days', 'calories_per_day', 'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'duration_days' => 'integer',
        'calories_per_day' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MealPlanVariant::class, 'meal_plan_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'meal_plan_items', 'meal_plan_id', 'menu_item_id')->withTimestamps();
    }
}



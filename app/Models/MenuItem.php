<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'calories',
        'protein',
        'carb',
        'fat',
        'image_url',
        'object_reason',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'calories' => 'integer',
        'protein' => 'integer',
        'carb' => 'integer',
        'fat' => 'integer',
        'object_reason' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_items', 'menu_item_id', 'meal_plan_id')->withTimestamps();
    }
}



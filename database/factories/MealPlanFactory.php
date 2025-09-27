<?php

namespace Database\Factories;

use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlan>
 */
class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => $this->faker->words(3, true),
            'name_ar' => $this->faker->words(3, true),
            'description_en' => $this->faker->paragraph,
            'description_ar' => $this->faker->paragraph,
            'active' => $this->faker->boolean(80),
            'duration_days' => $this->faker->randomElement([7, 30, 60, 90, 180]),
        ];
    }
}

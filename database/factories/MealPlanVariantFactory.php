<?php

namespace Database\Factories;

use App\Models\MealPlanVariant;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlanVariant>
 */
class MealPlanVariantFactory extends Factory
{
    protected $model = MealPlanVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'name_en' => $this->faker->randomElement(['Standard', 'Premium', 'Deluxe']),
            'name_ar' => $this->faker->randomElement(['عادي', 'مميز', 'فاخر']),
            'description_en' => $this->faker->sentence,
            'description_ar' => $this->faker->sentence,
            'grams' => $this->faker->randomElement([300, 400, 500, 600]),
            'active' => $this->faker->boolean(80),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use App\Models\MealPlan;
use App\Models\MealPlanVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'meal_plan_id' => MealPlan::factory(),
            'meal_plan_variant_id' => MealPlanVariant::factory(),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'duration_type' => $this->faker->randomElement(['week', 'month', '2_months', '3_months', '6_months']),
            'meals_per_day' => $this->faker->randomElement([1, 2, 3]),
            'total_meals' => $this->faker->numberBetween(7, 180),
            'status' => $this->faker->randomElement(['active', 'paused', 'cancelled']),
            'total_paid' => $this->faker->randomFloat(2, 50, 500),
            'payment_method' => $this->faker->randomElement(['stripe', 'paypal', null]),
            'next_renewal_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }
}

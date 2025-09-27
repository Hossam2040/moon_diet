<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subscription;
use App\Models\User;
use App\Models\MealPlan;
use App\Models\MealPlanVariant;
use Carbon\Carbon;

// Create a test subscription with correct dates
$user = User::first();
$mealPlan = MealPlan::first();
$variant = MealPlanVariant::first();

if (!$user || !$mealPlan || !$variant) {
    echo "Missing required data. Please create user, meal plan, and variant first.\n";
    exit;
}

// Create subscription with correct dates
$startDate = Carbon::today();
$endDate = Carbon::today()->addMonth();

$subscription = Subscription::create([
    'user_id' => $user->id,
    'meal_plan_id' => $mealPlan->id,
    'meal_plan_variant_id' => $variant->id,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'duration_type' => 'month',
    'meals_per_day' => 2,
    'total_meals' => 60, // 30 days * 2 meals
    'status' => 'active',
    'total_paid' => 100.00,
    'payment_method' => 'test'
]);

echo "Created subscription with ID: " . $subscription->id . "\n";
echo "Start date: " . $subscription->start_date->format('Y-m-d') . "\n";
echo "End date: " . $subscription->end_date->format('Y-m-d') . "\n";
echo "Days: " . $subscription->end_date->diffInDays($subscription->start_date) . "\n";
echo "Total meals: " . $subscription->total_meals . "\n";

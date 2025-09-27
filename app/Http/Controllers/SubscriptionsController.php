<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\MealPlanPrice;
use App\Models\MealPlanVariant;
use App\Models\Subscription;
use App\Models\SubscriptionMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) ($request->query('per_page', 15));
        $subs = Subscription::with(['plan'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
        return response()->json($subs);
    }

    public function quote(Request $request)
    {
        $validated = $request->validate([
            'meal_plan_id' => ['required', 'integer', 'exists:meal_plans,id'],
            'meal_plan_variant_id' => ['required', 'integer', 'exists:meal_plan_variants,id'],
            'duration_type' => ['required', 'string', 'in:week,month,2_months,3_months,6_months'],
            'meals_per_day' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $plan = MealPlan::where('active', true)->findOrFail($validated['meal_plan_id']);
        $variant = MealPlanVariant::where('meal_plan_id', $plan->id)->where('active', true)->findOrFail($validated['meal_plan_variant_id']);
        $price = MealPlanPrice::where('meal_plan_variant_id', $variant->id)
            ->where('duration_type', $validated['duration_type'])
            ->where('meals_per_day', $validated['meals_per_day'])
            ->firstOrFail();

        $days = match ($validated['duration_type']) {
            'week' => 7,
            'month' => 30,
            '2_months' => 60,
            '3_months' => 90,
            '6_months' => 180,
            default => $plan->duration_days,
        };
        $totalMeals = $days * (int)$validated['meals_per_day'];

        return response()->json([
            'plan_id' => $plan->id,
            'variant_id' => $variant->id,
            'grams' => $variant->grams,
            'duration_type' => $validated['duration_type'],
            'days' => $days,
            'meals_per_day' => (int)$validated['meals_per_day'],
            'total_meals' => $totalMeals,
            'price' => (float)$price->price,
        ]);
    }

    public function subscribe(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'meal_plan_id' => ['required', 'integer', 'exists:meal_plans,id'],
            'meal_plan_variant_id' => ['required', 'integer', 'exists:meal_plan_variants,id'],
            'duration_type' => ['required', 'string', 'in:week,month,2_months,3_months,6_months'],
            'meals_per_day' => ['required', 'integer', 'in:1,2,3'],
            'start_date' => ['nullable', 'date'],
        ]);

        $plan = MealPlan::where('active', true)->findOrFail($validated['meal_plan_id']);
        $variant = MealPlanVariant::where('meal_plan_id', $plan->id)->where('active', true)->findOrFail($validated['meal_plan_variant_id']);
        $price = MealPlanPrice::where('meal_plan_variant_id', $variant->id)
            ->where('duration_type', $validated['duration_type'])
            ->where('meals_per_day', $validated['meals_per_day'])
            ->firstOrFail();

        $days = match ($validated['duration_type']) {
            'week' => 7,
            'month' => 30,
            '2_months' => 60,
            '3_months' => 90,
            '6_months' => 180,
            default => $plan->duration_days,
        };
        $totalMeals = $days * (int)$validated['meals_per_day'];

        $start = !empty($validated['start_date']) ? Carbon::parse($validated['start_date']) : today();
        $end = (clone $start)->addDays($days);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'meal_plan_id' => $plan->id,
            'meal_plan_variant_id' => $variant->id,
            'start_date' => $start,
            'end_date' => $end,
            'duration_type' => $validated['duration_type'],
            'meals_per_day' => (int)$validated['meals_per_day'],
            'total_meals' => $totalMeals,
            'status' => 'active',
            'total_paid' => $price->price,
            'payment_method' => null,
        ]);
        return response()->json($sub, 201);
    }

    public function createTestSubscription(Request $request)
    {
        $user = $request->user();
        
        try {
            // Create a test subscription with correct dates
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addMonth();
            
            $sub = Subscription::create([
                'user_id' => $user->id,
                'meal_plan_id' => 1,
                'meal_plan_variant_id' => 1,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_type' => 'month',
                'meals_per_day' => 2,
                'total_meals' => 60,
                'status' => 'active',
                'total_paid' => 100.00,
                'payment_method' => 'test'
            ]);
            
            return response()->json([
                'message' => 'Test subscription created successfully',
                'subscription' => $sub,
                'days' => $endDate->diffInDays($startDate),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating test subscription',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 422);
        }
    }

    // public function setMeals(Request $request, int $id)
    // {
    //     $user = $request->user();
    //     $sub = Subscription::with('plan.items')->where('user_id', $user->id)->findOrFail($id);

    //     // Handle both JSON and Form Data
    //     $selections = $request->input('selections');
        
    //     // If selections is a string (from form data), decode it
    //     if (is_string($selections)) {
    //         $selections = json_decode($selections, true);
    //     }
        
    //     // If no selections in request, try to get from individual fields
    //     if (!$selections) {
    //         $selections = [];
    //         $dates = $request->input('date', []);
    //         $menuItemIds = $request->input('menu_item_id', []);
            
    //         // Handle single values (not arrays)
    //         if (!is_array($dates) && !is_array($menuItemIds)) {
    //             if ($dates && $menuItemIds) {
    //                 $selections[] = [
    //                     'date' => $dates,
    //                     'menu_item_id' => (int)$menuItemIds
    //                 ];
    //             }
    //         } elseif (is_array($dates) && is_array($menuItemIds)) {
    //             for ($i = 0; $i < count($dates); $i++) {
    //                 if (isset($dates[$i]) && isset($menuItemIds[$i])) {
    //                     $selections[] = [
    //                         'date' => $dates[$i],
    //                         'menu_item_id' => (int)$menuItemIds[$i]
    //                     ];
    //                 }
    //             }
    //         }
    //     }
        
    //     // Validate selections
    //     if (empty($selections)) {
    //         return response()->json(['message' => 'selections_required'], 422);
    //     }
        
    //     // Validate each selection
    //     foreach ($selections as $selection) {
    //         if (!isset($selection['date']) || !isset($selection['menu_item_id'])) {
    //             return response()->json(['message' => 'invalid_selection_format'], 422);
    //         }
    //     }
        
    //     $validated = ['selections' => $selections];

    //     // Fix date calculation - ensure end_date is after start_date
    //     if ($sub->end_date->lt($sub->start_date)) {
    //         return response()->json([
    //             'message' => 'invalid_subscription_dates',
    //             'start_date' => $sub->start_date->format('Y-m-d'),
    //             'end_date' => $sub->end_date->format('Y-m-d'),
    //             'error' => 'end_date must be after start_date'
    //         ], 422);
    //     }
        
    //     $days = $sub->end_date->diffInDays($sub->start_date);
        
    //     // Debug: Log subscription details
    //     \Log::info('Subscription details:', [
    //         'id' => $sub->id,
    //         'start_date' => $sub->start_date->format('Y-m-d'),
    //         'end_date' => $sub->end_date->format('Y-m-d'),
    //         'days' => $days,
    //         'total_meals' => $sub->total_meals
    //     ]);
    //     $allowedItemIds = $sub->plan->items()->pluck('menu_items.id')->all();
    //     $today = now()->startOfDay();
    //     $subscriptionStart = $sub->start_date->startOfDay();
    //     $minAdvanceDays = 3; // Minimum 3 days advance notice

    //     foreach ($validated['selections'] as $sel) {
    //         // Debug: Log each selection
    //         \Log::info('Processing selection:', $sel);
            
    //         // Parse the date
    //         $targetDate = Carbon::parse($sel['date']);
            
    //         // Check if date is within subscription range
    //         if ($targetDate->lt($sub->start_date) || $targetDate->gt($sub->end_date)) {
    //             return response()->json([
    //                 'message' => 'invalid_date',
    //                 'valid_range' => $sub->start_date->format('Y-m-d') . ' to ' . $sub->end_date->format('Y-m-d'),
    //                 'subscription_days' => $days,
    //                 'start_date' => $sub->start_date->format('Y-m-d'),
    //                 'end_date' => $sub->end_date->format('Y-m-d'),
    //                 'debug' => [
    //                     'target_date' => $targetDate->format('Y-m-d'),
    //                     'start_date' => $sub->start_date->format('Y-m-d'),
    //                     'end_date' => $sub->end_date->format('Y-m-d')
    //                 ]
    //             ], 422);
    //         }
            
    //         // Check if menu item is allowed for this plan
    //         if (!in_array($sel['menu_item_id'], $allowedItemIds, true)) {
    //             return response()->json(['message' => 'item_not_allowed_for_plan'], 422);
    //         }
            
    //         // Check if the target date is at least 3 days in the future
    //         if ($targetDate->diffInDays($today) < $minAdvanceDays) {
    //             return response()->json([
    //                 'message' => 'insufficient_advance_notice',
    //                 'target_date' => $targetDate->format('Y-m-d'),
    //                 'minimum_advance_days' => $minAdvanceDays,
    //                 'days_remaining' => $targetDate->diffInDays($today)
    //             ], 422);
    //         }
    //     }

    //     // Add or update meals (don't replace all)
    //     \DB::transaction(function () use ($sub, $validated) {
    //         foreach ($validated['selections'] as $sel) {
    //             $targetDate = Carbon::parse($sel['date']);
    //             $dayIndex = $sub->start_date->diffInDays($targetDate);
                
    //             // Check if meal already exists for this date and update or create
    //             $existingMeal = $sub->meals()
    //                 ->where('day_index', $dayIndex)
    //                 ->where('menu_item_id', $sel['menu_item_id'])
    //                 ->first();
                
    //             if (!$existingMeal) {
    //                 // Create new meal
    //                 $sub->meals()->create([
    //                     'day_index' => $dayIndex,
    //                     'menu_item_id' => (int)$sel['menu_item_id'],
    //                 ]);
    //             }
    //         }
    //     });

    //     return response()->json(['saved' => true]);
    // }

    public function setMeals(Request $request, int $id)
{
    $user = $request->user();
    $sub = Subscription::with('plan.items')->where('user_id', $user->id)->findOrFail($id);

    // Handle both JSON and Form Data
    $selections = $request->input('selections');
    
    if (is_string($selections)) {
        $selections = json_decode($selections, true);
    }
    
    if (!$selections) {
        $selections = [];
        $dates = $request->input('date', []);
        $menuItemIds = $request->input('menu_item_id', []);
        
        if (!is_array($dates) && !is_array($menuItemIds)) {
            if ($dates && $menuItemIds) {
                $selections[] = [
                    'date' => $dates,
                    'menu_item_id' => (int)$menuItemIds
                ];
            }
        } elseif (is_array($dates) && is_array($menuItemIds)) {
            for ($i = 0; $i < count($dates); $i++) {
                if (isset($dates[$i]) && isset($menuItemIds[$i])) {
                    $selections[] = [
                        'date' => $dates[$i],
                        'menu_item_id' => (int)$menuItemIds[$i]
                    ];
                }
            }
        }
    }
    
    if (empty($selections)) {
        return response()->json(['message' => 'selections_required'], 422);
    }
    
    foreach ($selections as $selection) {
        if (!isset($selection['date']) || !isset($selection['menu_item_id'])) {
            return response()->json(['message' => 'invalid_selection_format'], 422);
        }
    }
    
    $validated = ['selections' => $selections];

    if ($sub->end_date->lt($sub->start_date)) {
        return response()->json([
            'message' => 'invalid_subscription_dates',
            'start_date' => $sub->start_date->format('Y-m-d'),
            'end_date' => $sub->end_date->format('Y-m-d'),
            'error' => 'end_date must be after start_date'
        ], 422);
    }
    
    $days = $sub->end_date->diffInDays($sub->start_date);
    
    $allowedItemIds = $sub->plan->items()->pluck('menu_items.id')->all();
    $today = now()->startOfDay();
    $subscriptionStart = $sub->start_date->startOfDay();
    $minAdvanceDays = 3; // Minimum 3 days advance notice

    foreach ($validated['selections'] as $sel) {
        $targetDate = Carbon::parse($sel['date']);

        if ($targetDate->lt($sub->start_date) || $targetDate->gt($sub->end_date)) {
            return response()->json([
                'message' => 'invalid_date',
                'valid_range' => $sub->start_date->format('Y-m-d') . ' to ' . $sub->end_date->format('Y-m-d'),
                'subscription_days' => $days,
                'start_date' => $sub->start_date->format('Y-m-d'),
                'end_date' => $sub->end_date->format('Y-m-d'),
                'debug' => [
                    'target_date' => $targetDate->format('Y-m-d'),
                    'start_date' => $sub->start_date->format('Y-m-d'),
                    'end_date' => $sub->end_date->format('Y-m-d')
                ]
            ], 422);
        }

        if (!in_array($sel['menu_item_id'], $allowedItemIds, true)) {
            return response()->json(['message' => 'item_not_allowed_for_plan'], 422);
        }

        // ✅ التعديل هنا: نحسب الفرق بين اليوم والتاريخ المطلوب بشكل signed
        $daysRemaining = $today->diffInDays($targetDate, false);

        if ($daysRemaining < $minAdvanceDays) {
            return response()->json([
                'message' => 'insufficient_advance_notice',
                'target_date' => $targetDate->format('Y-m-d'),
                'minimum_advance_days' => $minAdvanceDays,
                'days_remaining' => $daysRemaining
            ], 422);
        }
    }

    \DB::transaction(function () use ($sub, $validated) {
        foreach ($validated['selections'] as $sel) {
            $targetDate = Carbon::parse($sel['date']);
            $dayIndex = $sub->start_date->diffInDays($targetDate);
            
            $existingMeal = $sub->meals()
                ->where('day_index', $dayIndex)
                ->where('menu_item_id', $sel['menu_item_id'])
                ->first();
            
            if (!$existingMeal) {
                $sub->meals()->create([
                    'day_index' => $dayIndex,
                    'menu_item_id' => (int)$sel['menu_item_id'],
                ]);
            }
        }
    });

    return response()->json(['saved' => true]);
}


    public function removeMeal(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'day_index' => ['required', 'integer', 'min:0'],
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);

        $days = $sub->end_date->diffInDays($sub->start_date);
        $today = now()->startOfDay();
        $subscriptionStart = $sub->start_date->startOfDay();
        $minAdvanceDays = 3;

        // Check if day_index is within subscription range
        if ($validated['day_index'] < 0 || $validated['day_index'] >= $days) {
            return response()->json(['message' => 'invalid_day_index'], 422);
        }

        // Calculate the actual date for this day_index
        $targetDate = $subscriptionStart->copy()->addDays($validated['day_index']);
        
        // Check if the target date is at least 3 days in the future
        if ($targetDate->diffInDays($today) < $minAdvanceDays) {
            return response()->json([
                'message' => 'insufficient_advance_notice',
                'target_date' => $targetDate->format('Y-m-d'),
                'minimum_advance_days' => $minAdvanceDays,
                'days_remaining' => $targetDate->diffInDays($today)
            ], 422);
        }

        // Remove the meal
        $deleted = $sub->meals()
            ->where('day_index', $validated['day_index'])
            ->where('menu_item_id', $validated['menu_item_id'])
            ->delete();

        if ($deleted) {
            return response()->json(['removed' => true]);
        } else {
            return response()->json(['message' => 'meal_not_found'], 404);
        }
    }

    public function getMeals(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);
        $meals = $sub->meals()->with('item')->orderBy('day_index')->get();
        
        // Add actual dates to meals
        $mealsWithDates = $meals->map(function ($meal) use ($sub) {
            $actualDate = $sub->start_date->copy()->addDays($meal->day_index);
            
            // Create clean meal object with only needed fields
            return [
                'id' => $meal->id,
                'menu_item_id' => $meal->menu_item_id,
                'actual_date' => $actualDate->format('Y-m-d'),
                'actual_date_formatted' => $actualDate->format('Y-m-d H:i:s'),
                'day_name' => $actualDate->format('l'), // Day name (Monday, Tuesday, etc.)
                'can_modify' => $actualDate->diffInDays(now()) >= 3,
                'item' => $meal->item
            ];
        });
        
        return response()->json([
            'subscription' => [
                'id' => $sub->id,
                'start_date' => $sub->start_date->format('Y-m-d'),
                'end_date' => $sub->end_date->format('Y-m-d'),
                'total_meals' => (int)$sub->total_meals,
                'meals_per_day' => (int)$sub->meals_per_day,
                'days' => $sub->end_date->diffInDays($sub->start_date),
                'status' => $sub->status,
            ],
            'meals' => $mealsWithDates,
            'summary' => [
                'total_selected' => $meals->count(),
                'remaining' => (int)$sub->total_meals - $meals->count(),
                'progress_percentage' => round(($meals->count() / (int)$sub->total_meals) * 100, 2)
            ],
            'available_dates' => [
                'earliest_available' => now()->addDays(3)->format('Y-m-d'),
                'latest_available' => $sub->end_date->format('Y-m-d'),
                'today' => now()->format('Y-m-d')
            ]
        ]);
    }

    public function pause(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);
        $sub->status = 'paused';
        $sub->save();
        return response()->json($sub);
    }

    public function resume(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);
        $sub->status = 'active';
        $sub->save();
        return response()->json($sub);
    }

    public function cancel(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);
        $sub->status = 'cancelled';
        $sub->next_renewal_at = null;
        $sub->save();
        return response()->json($sub);
    }
}



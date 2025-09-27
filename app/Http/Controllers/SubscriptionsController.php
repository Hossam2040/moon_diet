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

    public function setMeals(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::with('plan.items')->where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'selections' => ['required', 'array', 'min:1'],
            'selections.*.day_index' => ['required', 'integer', 'min:0'],
            'selections.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);

        $days = $sub->end_date->diffInDays($sub->start_date);
        $allowedItemIds = $sub->plan->items()->pluck('menu_items.id')->all();

        foreach ($validated['selections'] as $sel) {
            if ($sel['day_index'] < 0 || $sel['day_index'] >= $days) {
                return response()->json(['message' => 'invalid_day_index'], 422);
            }
            if (!in_array($sel['menu_item_id'], $allowedItemIds, true)) {
                return response()->json(['message' => 'item_not_allowed_for_plan'], 422);
            }
        }

        // Ensure count matches exactly: total_meals
        if (count($validated['selections']) !== (int)$sub->total_meals) {
            return response()->json(['message' => 'invalid_total_meals_count', 'expected' => (int)$sub->total_meals], 422);
        }

        // Replace all selections atomically
        \DB::transaction(function () use ($sub, $validated) {
            $sub->meals()->delete();
            $bulk = [];
            $now = now();
            foreach ($validated['selections'] as $sel) {
                $bulk[] = [
                    'subscription_id' => $sub->id,
                    'day_index' => (int)$sel['day_index'],
                    'menu_item_id' => (int)$sel['menu_item_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            SubscriptionMeal::insert($bulk);
        });

        return response()->json(['saved' => true]);
    }

    public function getMeals(Request $request, int $id)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->findOrFail($id);
        $meals = $sub->meals()->with('item')->orderBy('day_index')->get();
        return response()->json([
            'total_meals' => (int)$sub->total_meals,
            'meals_per_day' => (int)$sub->meals_per_day,
            'days' => $sub->end_date->diffInDays($sub->start_date),
            'selections' => $meals,
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



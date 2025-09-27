<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\MenuItem;
use App\Models\MealPlanVariant;
use App\Models\MealPlanPrice;
use Illuminate\Http\Request;

class MealPlansController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $plans = MealPlan::with(['variants' => function ($q) {
            $q->where('active', true)->with(['prices']);
        }])
            ->where('active', true)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($plans);
    }

    public function adminIndex(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $plans = MealPlan::with(['variants.prices'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'calories_per_day' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
        $plan = MealPlan::create($validated);
        return response()->json($plan, 201);
    }

    public function show(int $id)
    {
        $plan = MealPlan::with(['variants.prices'])->findOrFail($id);
        return response()->json($plan);
    }

    public function update(Request $request, int $id)
    {
        $plan = MealPlan::findOrFail($id);
        $validated = $request->validate([
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'calories_per_day' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
        $plan->fill($validated)->save();
        return response()->json($plan);
    }

    public function destroy(int $id)
    {
        $plan = MealPlan::findOrFail($id);
        $plan->delete();
        return response()->json(['deleted' => true]);
    }

    // Items per plan (public read)
    public function listItems(int $id, Request $request)
    {
        $plan = MealPlan::findOrFail($id);
        $perPage = (int) ($request->query('per_page', 15));
        $items = $plan->items()->paginate($perPage);
        return response()->json($items);
    }

    // Admin attach/detach items
    public function attachItem(Request $request, int $id)
    {
        $plan = MealPlan::findOrFail($id);
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);
        $plan->items()->syncWithoutDetaching([$validated['menu_item_id']]);
        return response()->json(['attached' => true]);
    }

    public function detachItem(Request $request, int $id)
    {
        $plan = MealPlan::findOrFail($id);
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);
        $plan->items()->detach([$validated['menu_item_id']]);
        return response()->json(['detached' => true]);
    }

    // Admin: variants
    public function listVariants(int $id)
    {
        $plan = MealPlan::findOrFail($id);
        return response()->json($plan->variants()->with('prices')->get());
    }

    public function addVariant(Request $request, int $id)
    {
        $plan = MealPlan::findOrFail($id);
        $validated = $request->validate([
            'grams' => ['required', 'integer', 'min:50', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);
        $variant = MealPlanVariant::create([
            'meal_plan_id' => $plan->id,
            'grams' => (int)$validated['grams'],
            'active' => $validated['active'] ?? true,
        ]);
        return response()->json($variant, 201);
    }

    // Admin: prices for a variant
    public function addVariantPrice(Request $request, int $planId, int $variantId)
    {
        $plan = MealPlan::findOrFail($planId);
        $variant = MealPlanVariant::where('meal_plan_id', $plan->id)->findOrFail($variantId);
        $validated = $request->validate([
            'duration_type' => ['required', 'string', 'in:week,month,2_months,3_months,6_months'],
            'meals_per_day' => ['required', 'integer', 'in:1,2,3'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
        $price = MealPlanPrice::create([
            'meal_plan_variant_id' => $variant->id,
            'duration_type' => $validated['duration_type'],
            'meals_per_day' => (int)$validated['meals_per_day'],
            'price' => $validated['price'],
        ]);
        return response()->json($price, 201);
    }
}



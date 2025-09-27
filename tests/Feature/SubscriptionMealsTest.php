<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Models\MealPlan;
use App\Models\MealPlanVariant;
use App\Models\MealPlanPrice;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\SubscriptionMeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubscriptionMealsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $subscription;
    protected $menuItems;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create menu category
        $category = MenuCategory::factory()->create([
            'name_en' => 'Test Category',
            'name_ar' => 'فئة الاختبار',
            'active' => true
        ]);
        
        // Create menu items
        $this->menuItems = MenuItem::factory()->count(5)->create([
            'category_id' => $category->id,
            'name_en' => 'Test Meal',
            'name_ar' => 'وجبة الاختبار',
            'price' => 25.00,
            'calories' => 500,
            'protein' => 30,
            'carb' => 40,
            'fat' => 20,
            'active' => true
        ]);
        
        // Create meal plan
        $mealPlan = MealPlan::factory()->create([
            'name_en' => 'Test Plan',
            'name_ar' => 'خطة الاختبار',
            'active' => true
        ]);
        
        // Attach menu items to meal plan
        $mealPlan->items()->attach($this->menuItems->pluck('id'));
        
        // Create meal plan variant
        $variant = MealPlanVariant::factory()->create([
            'meal_plan_id' => $mealPlan->id,
            'name_en' => 'Standard',
            'name_ar' => 'عادي',
            'grams' => 500,
            'active' => true
        ]);
        
        // Create meal plan price
        MealPlanPrice::factory()->create([
            'meal_plan_variant_id' => $variant->id,
            'duration_type' => 'week',
            'meals_per_day' => 2,
            'price' => 100.00
        ]);
        
        // Create subscription
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'meal_plan_id' => $mealPlan->id,
            'meal_plan_variant_id' => $variant->id,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'duration_type' => 'week',
            'meals_per_day' => 2,
            'total_meals' => 14, // 7 days * 2 meals per day
            'status' => 'active',
            'total_paid' => 100.00
        ]);
    }

    /** @test */
    public function user_can_get_meals_for_their_subscription()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/subscriptions/{$this->subscription->id}/meals");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_meals',
                'meals_per_day',
                'days',
                'selections'
            ])
            ->assertJson([
                'total_meals' => 14,
                'meals_per_day' => 2,
                'days' => 7
            ]);
    }

    /** @test */
    public function user_can_set_meals_for_their_subscription()
    {
        $selections = [];
        $menuItemIds = $this->menuItems->pluck('id')->toArray();
        
        // Create selections for all 14 meals (7 days * 2 meals per day)
        for ($i = 0; $i < 14; $i++) {
            $selections[] = [
                'day_index' => $i,
                'menu_item_id' => $menuItemIds[$i % count($menuItemIds)]
            ];
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(200)
            ->assertJson(['saved' => true]);

        // Verify meals were saved
        $this->assertDatabaseCount('subscription_meals', 14);
        
        // Verify specific meal was saved
        $this->assertDatabaseHas('subscription_meals', [
            'subscription_id' => $this->subscription->id,
            'day_index' => 0,
            'menu_item_id' => $menuItemIds[0]
        ]);
    }

    /** @test */
    public function user_cannot_set_meals_with_invalid_day_index()
    {
        $selections = [
            [
                'day_index' => -1, // Invalid: negative day index
                'menu_item_id' => $this->menuItems->first()->id
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'invalid_day_index']);
    }

    /** @test */
    public function user_cannot_set_meals_with_day_index_out_of_range()
    {
        $selections = [
            [
                'day_index' => 10, // Invalid: beyond subscription duration (7 days)
                'menu_item_id' => $this->menuItems->first()->id
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'invalid_day_index']);
    }

    /** @test */
    public function user_cannot_set_meals_with_invalid_menu_item()
    {
        $selections = [
            [
                'day_index' => 0,
                'menu_item_id' => 99999 // Invalid: non-existent menu item
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_cannot_set_meals_with_item_not_allowed_for_plan()
    {
        // Create a menu item that's not attached to the meal plan
        $otherMenuItem = MenuItem::factory()->create([
            'name_en' => 'Other Meal',
            'name_ar' => 'وجبة أخرى',
            'price' => 30.00
        ]);

        $selections = [
            [
                'day_index' => 0,
                'menu_item_id' => $otherMenuItem->id
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'item_not_allowed_for_plan']);
    }

    /** @test */
    public function user_cannot_set_meals_with_incorrect_total_count()
    {
        $selections = [
            [
                'day_index' => 0,
                'menu_item_id' => $this->menuItems->first()->id
            ]
            // Only 1 selection instead of 14
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'invalid_total_meals_count',
                'expected' => 14
            ]);
    }

    /** @test */
    public function user_cannot_access_another_users_subscription_meals()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/subscriptions/{$this->subscription->id}/meals");

        $response->assertStatus(404);
    }

    /** @test */
    public function user_cannot_set_meals_for_another_users_subscription()
    {
        $otherUser = User::factory()->create();
        $selections = [
            [
                'day_index' => 0,
                'menu_item_id' => $this->menuItems->first()->id
            ]
        ];

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function setting_meals_replaces_existing_meals()
    {
        // First, set some meals
        $selections1 = [];
        for ($i = 0; $i < 14; $i++) {
            $selections1[] = [
                'day_index' => $i,
                'menu_item_id' => $this->menuItems->first()->id
            ];
        }

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections1
            ]);

        // Verify first set of meals
        $this->assertDatabaseCount('subscription_meals', 14);
        $this->assertDatabaseHas('subscription_meals', [
            'subscription_id' => $this->subscription->id,
            'day_index' => 0,
            'menu_item_id' => $this->menuItems->first()->id
        ]);

        // Now set different meals
        $selections2 = [];
        for ($i = 0; $i < 14; $i++) {
            $selections2[] = [
                'day_index' => $i,
                'menu_item_id' => $this->menuItems->last()->id
            ];
        }

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
                'selections' => $selections2
            ]);

        // Verify old meals were replaced
        $this->assertDatabaseCount('subscription_meals', 14);
        $this->assertDatabaseMissing('subscription_meals', [
            'subscription_id' => $this->subscription->id,
            'day_index' => 0,
            'menu_item_id' => $this->menuItems->first()->id
        ]);
        $this->assertDatabaseHas('subscription_meals', [
            'subscription_id' => $this->subscription->id,
            'day_index' => 0,
            'menu_item_id' => $this->menuItems->last()->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_meals()
    {
        $response = $this->getJson("/api/subscriptions/{$this->subscription->id}/meals");
        $response->assertStatus(401);

        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/meals", [
            'selections' => []
        ]);
        $response->assertStatus(401);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuAdminController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\AddressesController;
use App\Http\Controllers\CouponsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MealPlansController;
use App\Http\Controllers\SubscriptionsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\DevMadaController;
use App\Http\Controllers\OffersController;

// Authentication endpoints (stateless; uses Sanctum tokens)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/otp/request', [AuthController::class, 'requestOtp']);
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/auth/guest', [AuthController::class, 'guest']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Public menu read endpoints
Route::get('/menu/categories', [MenuController::class, 'listCategories']);
Route::get('/menu/items', [MenuController::class, 'listItems']);

// Public offers endpoints
Route::get('/offers', [OffersController::class, 'index']);
Route::get('/offers/{id}', [OffersController::class, 'show']);

// Admin management endpoints (protect later with auth middleware)
Route::prefix('/admin')->middleware(['force.json','auth:sanctum','admin'])->group(function () {
    Route::get('/categories', [MenuAdminController::class, 'indexCategories']);
    Route::post('/categories', [MenuAdminController::class, 'storeCategory']);
    Route::get('/categories/{id}', [MenuAdminController::class, 'showCategory']);
    Route::patch('/categories/{id}', [MenuAdminController::class, 'updateCategory']);
    Route::put('/categories/{id}', [MenuAdminController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [MenuAdminController::class, 'destroyCategory']);

    Route::get('/items', [MenuAdminController::class, 'indexItems']);
    Route::post('/items', [MenuAdminController::class, 'storeItem']);
    Route::get('/items/{id}', [MenuAdminController::class, 'showItem']);
    Route::post('/items/{id}', [MenuAdminController::class, 'updateItem']);
    Route::patch('/items/{id}', [MenuAdminController::class, 'updateItem']);
    Route::delete('/items/{id}', [MenuAdminController::class, 'destroyItem']);

    // Users
    Route::get('/users', [UsersController::class, 'adminIndex']);
    Route::post('/users', [UsersController::class, 'adminStore']);
    
    // Deleted Users Management (must be before /users/{id} to avoid conflict)
    Route::get('/users/deleted', [UsersController::class, 'adminDeletedUsers']);
    //Route::get('/users/deleted/{id}', [UsersController::class, 'adminShowDeletedUser']);
    
    Route::get('/users/{id}', [UsersController::class, 'adminShow']);
    Route::put('/users/{id}', [UsersController::class, 'adminUpdate']);
    Route::patch('/users/{id}', [UsersController::class, 'adminUpdate']);
    Route::delete('/users/{id}', [UsersController::class, 'adminDestroy']);
    Route::post('/users/{id}/set-admin', [UsersController::class, 'adminSetAdmin']);


    Route::post('/users/{id}/restore', [UsersController::class, 'adminRestoreUser']);
    Route::delete('/users/{id}/force-delete', [UsersController::class, 'adminForceDeleteUser']);
    
    // Offers Management
    Route::get('/offers', [OffersController::class, 'adminIndex']);
    Route::post('/offers', [OffersController::class, 'adminStore']);
    Route::get('/offers/{id}', [OffersController::class, 'adminShow']);
    Route::put('/offers/{id}', [OffersController::class, 'adminUpdate']);
    Route::patch('/offers/{id}', [OffersController::class, 'adminUpdate']);
    Route::delete('/offers/{id}', [OffersController::class, 'adminDestroy']);
    Route::post('/offers/{id}/toggle-active', [OffersController::class, 'adminToggleActive']);

    // Plans items management
    Route::post('/plans/{id}/items/attach', [MealPlansController::class, 'attachItem']);
    Route::post('/plans/{id}/items/detach', [MealPlansController::class, 'detachItem']);

    // Plans variants & prices management
    Route::get('/plans/{id}/variants', [MealPlansController::class, 'listVariants']);
    Route::post('/plans/{id}/variants', [MealPlansController::class, 'addVariant']);
    Route::post('/plans/{planId}/variants/{variantId}/prices', [MealPlansController::class, 'addVariantPrice']);
});

// User self endpoints (to be protected later)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UsersController::class, 'me']);
    Route::put('/me', [UsersController::class, 'updateMe']);
    Route::delete('/me', [UsersController::class, 'deleteMe']);

    // Cart
    Route::get('/cart', [CartController::class, 'view']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::patch('/cart/update', [CartController::class, 'update']);
    Route::delete('/cart/remove', [CartController::class, 'remove']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [OrdersController::class, 'index']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);
    Route::post('/orders/checkout', [OrdersController::class, 'checkout']);

    // Addresses
    Route::get('/addresses', [AddressesController::class, 'index']);
    Route::post('/addresses', [AddressesController::class, 'store']);
    Route::patch('/addresses/{id}', [AddressesController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressesController::class, 'destroy']);

    // Coupons
    Route::post('/coupons/validate', [CouponsController::class, 'validateCode']);

    // Settings (admin only ideally)
    Route::get('/settings', [SettingsController::class, 'get']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Meal Plans (public read + admin manage endpoints here for simplicity)
    Route::get('/plans', [MealPlansController::class, 'index']);
    Route::get('/plans/{id}/items', [MealPlansController::class, 'listItems']);
    Route::get('/admin/plans', [MealPlansController::class, 'adminIndex']);
    Route::post('/admin/plans', [MealPlansController::class, 'store']);
    Route::get('/admin/plans/{id}', [MealPlansController::class, 'show']);
    Route::put('/admin/plans/{id}', [MealPlansController::class, 'update']);
    Route::delete('/admin/plans/{id}', [MealPlansController::class, 'destroy']);

    // Subscriptions
    Route::post('/subscriptions/quote', [SubscriptionsController::class, 'quote']);
    Route::get('/subscriptions', [SubscriptionsController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionsController::class, 'subscribe']);
    Route::post('/subscriptions/test', [SubscriptionsController::class, 'createTestSubscription']);
    Route::get('/subscriptions/{id}/meals', [SubscriptionsController::class, 'getMeals']);
    Route::post('/subscriptions/{id}/meals', [SubscriptionsController::class, 'setMeals']);
    Route::delete('/subscriptions/{id}/meals', [SubscriptionsController::class, 'removeMeal']);
    Route::post('/subscriptions/{id}/pause', [SubscriptionsController::class, 'pause']);
    Route::post('/subscriptions/{id}/resume', [SubscriptionsController::class, 'resume']);
    Route::post('/subscriptions/{id}/cancel', [SubscriptionsController::class, 'cancel']);

    // Payments
    Route::post('/payments/create-intent', [PaymentsController::class, 'createIntent']);
    Route::get('/payments/{id}/invoice', [PaymentsController::class, 'invoice']);
    Route::get('/payments/{id}/invoice.pdf', [PaymentsController::class, 'invoicePdf']);
});

// Webhooks (no auth)
Route::post('/payments/stripe/webhook', [PaymentsController::class, 'webhookStripe']);
Route::post('/payments/mada/callback', [PaymentsController::class, 'callbackMada']);

// Dev-only Mada mock endpoints
if (app()->environment(['local', 'testing'])) {
    Route::post('/_dev/mada/payments', [DevMadaController::class, 'create']);
    Route::get('/dev/mada/redirect', [DevMadaController::class, 'redirect']);
    Route::post('/_dev/mada/sign', [DevMadaController::class, 'sign']);
    Route::get('/dev/mada/auto-callback', [DevMadaController::class, 'autoCallback']);
}



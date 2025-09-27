<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('meal_plan_variant_id')->nullable()->after('meal_plan_id')->constrained('meal_plan_variants')->cascadeOnDelete();
            $table->string('duration_type')->nullable()->after('end_date');
            $table->unsignedTinyInteger('meals_per_day')->nullable()->after('duration_type');
            $table->unsignedInteger('total_meals')->nullable()->after('meals_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['meal_plan_variant_id']);
            $table->dropColumn(['meal_plan_variant_id', 'duration_type', 'meals_per_day', 'total_meals']);
        });
    }
};



<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add localized fields and image to meal_plans
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->string('image_url')->nullable()->after('description_en');
        });

        // Variants table: per plan weight (e.g., 100g, 150g)
        Schema::create('meal_plan_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained('meal_plans')->cascadeOnDelete();
            $table->unsignedInteger('grams');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['meal_plan_id', 'grams']);
        });

        // Prices table: duration x meals_per_day pricing per variant
        Schema::create('meal_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_variant_id')->constrained('meal_plan_variants')->cascadeOnDelete();
            // duration_type keeps values like week, month, 2_months, 3_months, 6_months
            $table->string('duration_type');
            $table->unsignedTinyInteger('meals_per_day'); // 1, 2, 3
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['meal_plan_variant_id', 'duration_type', 'meals_per_day'], 'uniq_variant_duration_meals');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_prices');
        Schema::dropIfExists('meal_plan_variants');

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en', 'description_ar', 'description_en', 'image_url']);
        });
    }
};



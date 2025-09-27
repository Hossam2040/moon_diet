<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            if (Schema::hasColumn('meal_plans', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('meal_plans', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->text('description')->nullable();
        });
    }
};



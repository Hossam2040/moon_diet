<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedInteger('protein')->nullable()->after('calories');
            $table->unsignedInteger('carb')->nullable()->after('protein');
            $table->unsignedInteger('fat')->nullable()->after('carb');
            $table->json('object_reason')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['protein', 'carb', 'fat', 'object_reason']);
        });
    }
};




<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            if (Schema::hasColumn('menu_categories', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('menu_categories', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->text('description')->nullable();
        });
    }
};




<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // sensible defaults
        \DB::table('settings')->insert([
            ['key' => 'tax_percent', 'value' => '0'],
            ['key' => 'delivery_fee', 'value' => '0'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};



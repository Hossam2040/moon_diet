<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Breakfast', 'description' => 'Morning healthy meals'],
            ['name' => 'Lunch', 'description' => 'Nutritious lunch options'],
            ['name' => 'Dinner', 'description' => 'Light dinner meals'],
            ['name' => 'Snacks', 'description' => 'Healthy snacks'],
        ];

        foreach ($categories as $catData) {
            $category = MenuCategory::firstOrCreate(['name' => $catData['name']], $catData);
            MenuItem::firstOrCreate([
                'category_id' => $category->id,
                'name' => $catData['name'].' Sample Item',
            ], [
                'description' => 'Sample description',
                'price' => 19.99,
                'calories' => 350,
                'image_url' => null,
            ]);
        }
    }
}



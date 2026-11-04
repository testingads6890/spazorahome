<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the room categories
        $categories = [
            ['name' => 'Living Room', 'description' => 'Furniture and decor for your living space'],
            ['name' => 'Bedroom', 'description' => 'Beds, wardrobes and bedroom storage'],
            ['name' => 'Kitchen & Dining', 'description' => 'Kitchen cabinets, tables and dining essentials'],
            ['name' => 'Bathroom', 'description' => 'Storage, mirrors and bathroom accessories'],
            ['name' => 'Office', 'description' => 'Desks, chairs and office organization'],
            ['name' => 'Kids & Baby', 'description' => 'Furniture and storage for children'],
            ['name' => 'Lighting', 'description' => 'Lamps, bulbs and lighting solutions'],
            ['name' => 'Textiles & Rugs', 'description' => 'Curtains, pillows, rugs and bedding'],
            ['name' => 'Decoration', 'description' => 'Picture frames, vases and home accessories'],
            ['name' => 'Outdoor', 'description' => 'Garden furniture and outdoor accessories'],
        ];

        foreach ($categories as $categoryData) {
            $categoryData['slug'] = Str::slug($categoryData['name']);
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }
    }
}
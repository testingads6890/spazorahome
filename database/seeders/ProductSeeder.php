<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Generator as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker)
    {
        // Get all categories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            // Fallback if no categories exist
            $categories = Category::whereIn('id', [1])->get();
        }
        
        // Get vendor (we know vendor ID 1 exists from earlier setup)
        $vendorId = 1;
        
        // House-brand (Jhanwars Homnex) product data
        $houseNames = [
            'POÄNG', 'LACK', 'EKET', 'KALLAX', 'BILLY', 'HEMNES', 'MALM', 
            'IVAR', 'TORSNÄS', 'FJÄLLBO', 'GRUNDTAL', 'SKUBB', 'DRÖNA', 
            'BOLMEN', 'SKÅDIS', 'RÅSKOG', 'TROFAST', 'FLISAT', 'MÖJLIGHET',
            'LIATORP', 'NORBERG', 'NORRVIKEN', 'HAVSTA', 'INGOLF', 'LERHAMN',
            'MONDAL', 'HYLLIS', 'LERBERG', 'VITTSJÖ', 'GLADOM', 'BJÖRKNÄS'
        ];
        
        $houseDescriptions = [
            'Solid wood construction with a timeless design',
            'Easy to assemble and disassemble',
            'Adjustable shelves for flexible storage',
            'Made from sustainable materials',
            'Space-saving design perfect for small spaces',
            'Classic design that never goes out of style',
            'Easy to clean and maintain',
            'Can be combined with other units for custom storage',
            'Available in multiple finishes and colors',
            'Designed for everyday use and durability'
        ];
        
        $productTypes = [
            'Chair', 'Table', 'Shelf', 'Cabinet', 'Desk', 'Bed', 'Sofa', 
            'Lamp', 'Rug', 'Curtain', 'Storage', 'Kitchen', 'Bathroom',
            'Lighting', 'Textile', 'Decoration', 'Baby', 'Kids', 'Office'
        ];
        
        $materials = [
            'Wood', 'Metal', 'Plastic', 'Glass', 'Fabric', 'Leather', 
            'Bamboo', 'Rattan', 'Stone', 'Concrete', 'Ceramic'
        ];
        
        $colors = [
            'White', 'Black', 'Brown', 'Gray', 'Beige', 'Blue', 'Green', 
            'Red', 'Yellow', 'Orange', 'Pink', 'Purple', 'Natural'
        ];
        
        $finishOptions = [
            'Matte', 'Glossy', 'Satin', 'Textured', 'Polished', 'Brushed'
        ];
        
        $sizes = [
            'W: 80 cm, D: 60 cm, H: 70 cm',
            'W: 120 cm, D: 40 cm, H: 75 cm',
            'W: 60 cm, D: 60 cm, H: 90 cm',
            'W: 100 cm, D: 50 cm, H: 180 cm',
            'Diameter: 60 cm, Height: 45 cm'
        ];
        
        $styles = [
            'Modern', 'Scandinavian', 'Traditional', 'Industrial', 
            'Minimalist', 'Rustic', 'Contemporary', 'Mid-Century'
        ];
        
        // Create 50 products for each category
        foreach ($categories as $category) {
            for ($i = 0; $i < 50; $i++) {
                // Use house-brand data for variety
                $useHouseData = rand(0, 1);
                
                if ($useHouseData) {
                    $productName = $houseNames[array_rand($houseNames)];
                    $description = $houseDescriptions[array_rand($houseDescriptions)];
                    $brand = 'Jhanwars Homnex';
                    $material = $materials[array_rand($materials)];
                    $color = $colors[array_rand($colors)];
                    $finish = $finishOptions[array_rand($finishOptions)];
                    $size = $sizes[array_rand($sizes)];
                    $style = $styles[array_rand($styles)];
                } else {
                    $productName = $faker->words(rand(2, 4), true);
                    $description = $faker->sentence;
                    $brand = $faker->company;
                    $material = $faker->word;
                    $color = $faker->colorName;
                    $finish = $faker->word;
                    $size = $faker->randomNumber(2) . 'x' . $faker->randomNumber(2) . 'x' . $faker->randomNumber(2) . ' cm';
                    $style = $faker->word;
                }
                
                $slug = Str::slug($productName);
                
                // Ensure unique slug
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                Product::create([
                    'category_id' => $category->id,
                    'vendor_id' => $vendorId,
                    'name' => $productName,
                    'slug' => $slug,
                    'description' => $description,
                    'short_description' => $faker->sentence,
                    'sku' => strtoupper(Str::random(8)),
                    'price' => rand(500, 50000) / 100, // ₹5 to ₹500
                    'sale_price' => rand(0, 1) ? rand(400, 45000) / 100 : null, // Sometimes on sale
                    'cost_price' => rand(300, 40000) / 100,
                    'stock_quantity' => rand(0, 100),
                    'low_stock_threshold' => rand(5, 15),
                    'is_active' => rand(0, 1) ? true : false,
                    'is_featured' => rand(0, 1) ? true : false,
                    'meta_title' => $productName . ' - Buy Online',
                    'meta_description' => $description . ' Shop now at our store.',
                    'vendor_status' => 'approved',
                    'vendor_rejection_reason' => null,
                    'brand' => $brand,
                    'material' => $material,
                    'color' => $color,
                    'finish' => $finish,
                    'size' => $size,
                    'style' => $style,
                    'product_type' => $productTypes[array_rand($productTypes)],
                    'weight' => rand(1, 50) . ' kg',
                    'dimensions' => $size,
                    'warranty' => rand(1, 5) . ' year',
                    'care_instructions' => 'Wipe clean with a damp cloth.',
                    'customization_info' => null,
                    'shipping_info' => 'Free shipping on orders over ₹1000',
                    'tags' => [$faker->word, $faker->word],
                    'is_new_arrival' => rand(0, 1) ? true : false,
                    'view_count' => rand(0, 1000),
                    'purchase_count' => rand(0, 100),
                ]);
            }
        }
    }
}
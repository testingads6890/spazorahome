<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Vendor;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random category
        $categoryId = Category::inRandomOrder()->first()->id ?? 1;
        
        // Get vendor (we know vendor ID 1 exists)
        $vendorId = 1;
        
        $name = $this->faker->words(rand(3, 5), true);
        $slug = \Illuminate\Support\Str::slug($name);
        
        // House-brand (Jhanwars Homnex) product names and data
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
        
        $productType = [
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
        
        // Select random house-brand values or use faker
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
            $productName = $name;
            $description = $this->faker->sentence;
            $brand = $this->faker->company;
            $material = $this->faker->word;
            $color = $this->faker->colorName;
            $finish = $this->faker->word;
            $size = $this->faker->randomNumber(2) . 'x' . $this->faker->randomNumber(2) . 'x' . $this->faker->randomNumber(2) . ' cm';
            $style = $this->faker->word;
        }
        
        return [
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $productName,
            'slug' => $slug,
            'description' => $description,
            'short_description' => $this->faker->sentence,
            'sku' => strtoupper(\Illuminate\Support\Str::random(8)),
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
            'product_type' => $productType[array_rand($productType)],
            'weight' => rand(1, 50) . ' kg',
            'dimensions' => $size,
            'warranty' => rand(1, 5) . ' year',
            'care_instructions' => 'Wipe clean with a damp cloth.',
            'customization_info' => null,
            'shipping_info' => 'Free shipping on orders over ₹1000',
            'tags' => [$this->faker->word, $this->faker->word],
            'is_new_arrival' => rand(0, 1) ? true : false,
            'view_count' => rand(0, 1000),
            'purchase_count' => rand(0, 100),
        ];
    }
}
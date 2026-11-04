<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_or_empty_queries_return_nothing(): void
    {
        $this->getJson('/search/suggest?q=a')->assertOk()->assertExactJson(['products' => [], 'categories' => []]);
        $this->getJson('/search/suggest')->assertOk()->assertExactJson(['products' => [], 'categories' => []]);
    }

    public function test_suggests_live_products_and_categories_only(): void
    {
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas', 'status' => true]);
        Category::create(['name' => 'Sofa Hidden', 'slug' => 'sofa-hidden', 'status' => false]);
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'business_name' => 'Pending Co', 'slug' => 'pending-co', 'email' => 'p@example.com', 'status' => 'pending', 'is_active' => false]);

        Product::create(['category_id' => $category->id, 'name' => 'Corner Sofa', 'slug' => 'corner-sofa', 'description' => 'd', 'sku' => 'SO-1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true]);
        Product::create(['category_id' => $category->id, 'name' => 'Hidden Sofa', 'slug' => 'hidden-sofa', 'description' => 'd', 'sku' => 'SO-2', 'price' => 10, 'stock_quantity' => 1, 'is_active' => false]);
        Product::create(['category_id' => $category->id, 'vendor_id' => $vendor->id, 'vendor_status' => 'pending', 'name' => 'Pending Sofa', 'slug' => 'pending-sofa', 'description' => 'd', 'sku' => 'SO-3', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true]);

        $data = $this->getJson('/search/suggest?q=sof')->assertOk()->json();

        $this->assertSame(['Corner Sofa'], array_column($data['products'], 'name'));
        $this->assertSame(['Sofas'], array_column($data['categories'], 'name'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_edit_or_delete_another_customers_review(): void
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        [$owner, $other] = [User::factory()->create(), User::factory()->create()];
        $owner->assignRole('customer');
        $other->assignRole('customer');

        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);
        $review = Review::create(['user_id' => $owner->id, 'product_id' => $product->id, 'rating' => 5]);

        $this->actingAs($other)->put(route('customer.reviews.update', $review), ['rating' => 1])->assertForbidden();
        $this->actingAs($other)->delete(route('customer.reviews.destroy', $review))->assertForbidden();
        $this->assertSame(5, $review->fresh()->rating);
    }
}

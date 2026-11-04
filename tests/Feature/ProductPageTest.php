<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'Solid oak sofa',
            'sku' => 'SKU-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true, 'tax_rate' => 12, 'hsn_code' => '9403',
        ], $o));
    }

    public function test_page_shows_price_discount_gst_specs_and_stock(): void
    {
        $p = $this->product(['sale_price' => 800, 'brand' => 'Acme', 'material' => 'Oak']);

        $this->get(route('products.show', $p))->assertOk()
            ->assertSee('Oak Sofa')->assertSee('₹800')->assertSee('₹1,000')->assertSee('-20%')
            ->assertSee('GST @ 12%')->assertSee('In stock')->assertSee('Acme')->assertSee('HSN code');
    }

    public function test_out_of_stock_disables_add_to_cart(): void
    {
        $p = $this->product(['stock_quantity' => 0]);

        $this->get(route('products.show', $p))->assertOk()->assertSee('Out of stock')->assertSee('disabled', false);
    }

    public function test_variants_render_as_selectable_options_that_post_to_the_variant_cart_route(): void
    {
        $p = $this->product();
        $red = ProductVariant::create(['product_id' => $p->id, 'sku' => 'R', 'price' => 1000, 'stock_quantity' => 2, 'attributes' => ['color' => 'Red', 'size' => 'L']]);
        ProductVariant::create(['product_id' => $p->id, 'sku' => 'B', 'price' => 900, 'stock_quantity' => 0, 'attributes' => ['color' => 'Blue']]);

        $this->get(route('products.show', $p))->assertOk()
            ->assertSee('Red / L')->assertSee(route('customer.cart.store', [$p, $red]), false)
            ->assertSee('Blue')->assertSee('disabled', false);
        $this->assertSame('Red / L', $red->label);
    }

    public function test_only_approved_reviews_and_a_correct_rating_summary_are_shown(): void
    {
        $p = $this->product();
        $u = User::factory()->create();
        Review::create(['user_id' => $u->id, 'product_id' => $p->id, 'rating' => 5, 'comment' => 'Loved it', 'is_approved' => true]);
        Review::create(['user_id' => User::factory()->create()->id, 'product_id' => $p->id, 'rating' => 3, 'comment' => 'Decent', 'is_approved' => true]);
        Review::create(['user_id' => User::factory()->create()->id, 'product_id' => $p->id, 'rating' => 1, 'comment' => 'Hidden spam', 'is_approved' => false]);

        $this->get(route('products.show', $p))->assertOk()
            ->assertSee('Loved it')->assertSee('Decent')->assertDontSee('Hidden spam')
            ->assertSee('4.0')->assertSee('2 reviews')->assertSee('Reviews (2)');
    }

    public function test_review_form_is_for_signed_in_customers(): void
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $p = $this->product();
        $buyer = User::factory()->create();
        $buyer->assignRole('customer');
        $order = \App\Models\Order::create(['user_id' => $buyer->id, 'order_number' => 'O1', 'status' => 'delivered', 'subtotal' => 1, 'total' => 1, 'currency' => 'INR']);
        $order->items()->create(['product_id' => $p->id, 'product_name' => 'x', 'product_sku' => 'x', 'quantity' => 1, 'unit_price' => 1, 'subtotal' => 1]);

        // Any signed-in customer can review from the product page; guests are asked to sign in.
        $this->actingAs($buyer)->get(route('products.show', $p))->assertSee('Write a review')->assertSee('Submit review');
        // A guest (no sign-in carried over from the request above) sees the sign-in prompt instead of the form.
        $this->app['auth']->forgetGuards();
        $this->get(route('products.show', $p))->assertDontSee('id="review-rating"', false)->assertSee('to write a review');
    }

    public function test_related_and_recently_viewed_products_appear(): void
    {
        $a = $this->product(['name' => 'Alpha Chair']);
        $b = $this->product(['name' => 'Beta Table']);

        $this->get(route('products.show', $a));
        $this->get(route('products.show', $b))->assertOk()
            ->assertSee('You may also like')->assertSee('Alpha Chair')
            ->assertSee('Recently viewed');
        // The product being viewed is never listed as its own recommendation.
        $this->assertSame(1, substr_count($this->get(route('products.show', $a))->getContent(), 'Recently viewed'));
    }

    public function test_hidden_products_are_not_found(): void
    {
        $this->get(route('products.show', $this->product(['is_active' => false])))->assertNotFound();
        $this->get(route('products.show', $this->product(['vendor_id' => null, 'vendor_status' => null])))->assertOk();
    }
}

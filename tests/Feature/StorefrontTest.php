<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Newsletter;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function product(Category $cat, string $name, int $price, ?int $sale = null, string $slug = null): Product
    {
        return Product::create([
            'category_id' => $cat->id, 'name' => $name, 'slug' => $slug ?? \Illuminate\Support\Str::slug($name), 'description' => 'x',
            'sku' => strtoupper(\Illuminate\Support\Str::random(6)), 'price' => $price, 'sale_price' => $sale,
            'stock_quantity' => 5, 'is_active' => true,
        ]);
    }

    public function test_search_filters_sorts_and_switches_view(): void
    {
        $cat = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $this->product($cat, 'Oak Sofa', 500);
        $this->product($cat, 'Pine Sofa', 300, 250);
        $this->product($cat, 'Glass Table', 900);

        $this->get(route('search', ['q' => 'sofa', 'sort' => 'price_asc']))->assertOk()
            ->assertSeeInOrder(['Pine Sofa', 'Oak Sofa'])->assertDontSee('Glass Table')->assertSee('-17%');
        $this->get(route('search', ['on_sale' => 1]))->assertOk()->assertSee('Pine Sofa')->assertDontSee('Oak Sofa');
        $this->get(route('search', ['view' => 'list']))->assertOk()->assertSee('list-view', false);
        $this->get(route('search', ['sort' => 'bogus']))->assertSessionHasErrors('sort');
        $this->get(route('search', ['q' => 'nothing-matches']))->assertOk()->assertSee('No products found');
    }

    public function test_listing_has_category_chips_a_filter_bar_and_a_progress_footer(): void
    {
        $sofas = Category::create(['name' => 'Sofas', 'slug' => 'sofas', 'description' => 'Seats for every living room.']);
        $beds = Category::create(['name' => 'Beds', 'slug' => 'beds']);
        foreach (range(1, 26) as $n) {
            $this->product($sofas, "Sofa {$n}", 100 + $n);
        }
        $this->product($beds, 'Teak Bed', 9000)->update(['material' => 'Teak']);

        $this->get(route('search'))->assertOk()
            ->assertSee('class="filter-bar"', false)->assertSee('cat-chip active', false)->assertSee('Showing 24 of 27 products')->assertSee('Show more')
            ->assertSee('Under ₹1,000')->assertSee('data-rail-track', false);
        $this->get(route('search', ['category' => $sofas->id]))->assertRedirect($sofas->shopUrl());
        $this->get($sofas->shopUrl())->assertOk()->assertSee('Seats for every living room.')->assertDontSee('Teak Bed')->assertSee('26 products');
        $this->get(route('search', ['min_price' => 5000, 'max_price' => 15000]))->assertOk()->assertSee('Teak Bed')->assertDontSee('Sofa 1')->assertDontSee('Show more');
        $this->get(route('search', ['material' => 'Teak']))->assertOk()->assertSee('Teak Bed')->assertSee('Showing 1 of 1 products');
    }

    public function test_home_page_shows_category_arches_the_room_mosaic_and_product_rails(): void
    {
        foreach (['Beds', 'Lighting', 'Sofas'] as $name) {
            $cat = Category::create(['name' => $name, 'slug' => strtolower($name)]);
        }
        $this->product($cat, 'Oak Sofa', 500)->update(['is_featured' => true, 'material' => 'Oak', 'color' => 'Walnut']);
        foreach (range(1, 4) as $n) {
            $this->product($cat, "Loved Sofa {$n}", 700)->update(['purchase_count' => $n]);
        }

        // Premium home page: room rail, collections mosaic, product rails, the featured edit and the services strip.
        $this->get(route('home'))->assertOk()
            ->assertSee('hx-rooms', false)->assertSee('mosaic n3', false)->assertSee('product-rail', false)
            ->assertSee('Oak Sofa')->assertSee('Loved Sofa 1')->assertSee(route('customer.order-tracking.index'), false)->assertSee('Add to cart');
    }

    public function test_home_page_renders_categories_products_and_live_deal(): void
    {
        $cat = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $p = $this->product($cat, 'Deal Sofa', 500);
        $p->update(['is_featured' => true]);
        \App\Models\Deal::create([
            'name' => 'Sofa Sale', 'product_id' => $p->id, 'original_price' => 500, 'deal_price' => 350,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'is_active' => true,
        ]);
        \App\Models\Deal::create([
            'name' => 'Expired Deal', 'product_id' => $p->id, 'original_price' => 500, 'deal_price' => 100,
            'starts_at' => now()->subDays(3), 'ends_at' => now()->subDay(), 'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Start with the room.')->assertSee('Sofas')->assertSee('Deal of the day')->assertSee('Sofa Sale')
            ->assertDontSee('Expired Deal')->assertSee('Deal Sofa');
    }

    public function test_newsletter_signup_is_idempotent_and_validated(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'A@Example.com'])->assertRedirect();
        $this->post(route('newsletter.subscribe'), ['email' => 'a@example.com'])->assertRedirect();

        $this->assertSame(1, Newsletter::count());
        $this->assertSame('a@example.com', Newsletter::first()->email);
        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    }

    public function test_header_shows_real_cart_count_for_a_logged_in_customer(): void
    {
        $user = User::factory()->create();
        $cat = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $p = $this->product($cat, 'Sofa', 10);
        $cart = Cart::create(['user_id' => $user->id, 'status' => 'active']);
        $cart->items()->create(['product_id' => $p->id, 'quantity' => 3, 'unit_price' => 10]);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('<span class="count">3</span>', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickViewAndStoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Store pages are switched off in the shop; these tests cover them with the switch on.
        config(['shop.stores_enabled' => true]);
    }

    public function test_store_pages_and_links_are_hidden_while_stores_are_off(): void
    {
        config(['shop.stores_enabled' => false]);
        $vendor = Vendor::create(['user_id' => User::factory()->create()->id, 'business_name' => 'Hidden Shop', 'slug' => 'hidden-shop', 'email' => 'hidden@example.com', 'status' => 'approved', 'is_active' => true]);
        $product = $this->product(['vendor_id' => $vendor->id, 'name' => 'Hidden Chair', 'vendor_status' => 'approved']);

        $this->get('/stores')->assertNotFound();
        $this->get('/stores/hidden-shop')->assertNotFound();
        $this->get(route('products.show', $product))->assertOk()->assertSee('Hidden Shop')->assertDontSee('/stores/hidden-shop', false);
    }

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'Solid oak sofa for everyday use',
            'sku' => 'S-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true,
        ], $o));
    }

    private function vendor(string $name = 'Acme Woods', string $status = 'approved', bool $active = true): Vendor
    {
        $u = User::factory()->create();

        return Vendor::create(['user_id' => $u->id, 'business_name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'email' => "v{$u->id}@e.com", 'city' => 'Jaipur', 'state' => 'Rajasthan', 'status' => $status, 'is_active' => $active]);
    }

    public function test_quick_view_returns_a_bare_fragment_with_price_stock_and_add_to_cart(): void
    {
        $p = $this->product(['sale_price' => 800]);

        $res = $this->get(route('products.quick-view', $p))->assertOk();
        $res->assertSee('Oak Sofa')->assertSee('₹800')->assertSee('₹1,000')->assertSee('In stock')->assertSee('Add to cart')->assertSee('View full details')->assertSee(route('customer.cart.store', $p), false);
        $this->assertStringNotContainsString('<html', $res->getContent()); // a fragment for the modal, not a full page
    }

    public function test_quick_view_sends_variant_products_to_the_full_page_and_flags_sold_out(): void
    {
        $p = $this->product();
        ProductVariant::create(['product_id' => $p->id, 'sku' => 'V', 'price' => 900, 'stock_quantity' => 3, 'attributes' => ['color' => 'Red']]);
        $this->get(route('products.quick-view', $p))->assertOk()->assertSee('From ₹900')->assertSee('Choose an option')->assertDontSee('name="quantity"', false);

        $this->get(route('products.quick-view', $this->product(['stock_quantity' => 0])))->assertOk()->assertSee('Out of stock')->assertDontSee('Add to cart');
    }

    public function test_quick_view_does_not_leak_hidden_products(): void
    {
        $this->get(route('products.quick-view', $this->product(['is_active' => false])))->assertNotFound();
        $v = $this->vendor();
        $this->get(route('products.quick-view', $this->product(['vendor_id' => $v->id, 'vendor_status' => 'pending'])))->assertNotFound();
    }

    public function test_product_cards_carry_a_quick_view_trigger_and_the_modal_shell_exists(): void
    {
        $p = $this->product();

        $this->get(route('search'))->assertOk()->assertSee('data-quick-view="' . route('products.quick-view', $p) . '"', false)->assertSee('id="quickView"', false);
    }

    public function test_stores_list_shows_only_live_sellers_with_live_product_counts(): void
    {
        $live = $this->vendor('Acme Woods');
        $this->product(['vendor_id' => $live->id, 'vendor_status' => 'approved']);
        $this->product(['vendor_id' => $live->id, 'vendor_status' => 'pending']); // not counted
        $this->vendor('Pending Store', 'pending', false);
        $this->vendor('Suspended Store', 'suspended', false);

        $this->get(route('stores.index'))->assertOk()
            ->assertSee('Acme Woods')->assertSee('1 product')->assertSee('Jaipur, Rajasthan')
            ->assertDontSee('Pending Store')->assertDontSee('Suspended Store');
    }

    public function test_stores_search_and_sort(): void
    {
        $a = $this->vendor('Alpha Woods');
        $b = $this->vendor('Beta Cane');
        foreach (range(1, 2) as $i) {
            $this->product(['vendor_id' => $b->id, 'vendor_status' => 'approved']);
        }

        $this->get(route('stores.index', ['q' => 'cane']))->assertOk()->assertSee('Beta Cane')->assertDontSee('Alpha Woods');
        $this->get(route('stores.index', ['sort' => 'products']))->assertSeeInOrder(['Beta Cane', 'Alpha Woods']);
        $this->get(route('stores.index', ['sort' => 'bogus']))->assertSessionHasErrors('sort');
        $this->get(route('stores.index', ['q' => 'zzz']))->assertOk()->assertSee('No stores found');
    }

    public function test_store_page_lists_only_that_sellers_live_products_by_slug(): void
    {
        $v = $this->vendor('Acme Woods');
        $other = $this->vendor('Other Store');
        $this->product(['name' => 'Acme Chair', 'vendor_id' => $v->id, 'vendor_status' => 'approved', 'price' => 500]);
        $this->product(['name' => 'Acme Hidden', 'vendor_id' => $v->id, 'vendor_status' => 'pending']);
        $this->product(['name' => 'Acme Inactive', 'vendor_id' => $v->id, 'vendor_status' => 'approved', 'is_active' => false]);
        $this->product(['name' => 'Other Table', 'vendor_id' => $other->id, 'vendor_status' => 'approved']);

        $this->get(route('stores.show', $v))->assertOk()
            ->assertSee('Acme Woods')->assertSee('Acme Chair')->assertSee('Jaipur, Rajasthan')
            ->assertDontSee('Acme Hidden')->assertDontSee('Acme Inactive')->assertDontSee('Other Table');
        $this->assertSame(url('/stores/acme-woods'), route('stores.show', $v));
    }

    public function test_store_pages_404_for_unapproved_or_unknown_sellers_and_never_show_private_data(): void
    {
        $pending = $this->vendor('Pending Store', 'pending', false);
        $live = $this->vendor('Live Store');
        $live->update(['tax_number' => '27ABCDE1234F1Z5', 'bank_details' => ['account_number' => '99887766']]);

        $this->get(route('stores.show', $pending))->assertNotFound();
        $this->get('/stores/does-not-exist')->assertNotFound();
        $this->get(route('stores.show', $live))->assertOk()->assertDontSee('27ABCDE1234F1Z5')->assertDontSee('99887766');
    }

    public function test_product_page_links_the_seller_to_their_store_and_nav_has_a_stores_link(): void
    {
        $v = $this->vendor('Acme Woods');
        $p = $this->product(['vendor_id' => $v->id, 'vendor_status' => 'approved']);

        $this->get(route('products.show', $p))->assertOk()->assertSee(route('stores.show', $v), false);
        $this->get(route('home'))->assertOk()->assertSee(route('stores.index'), false);
    }
}

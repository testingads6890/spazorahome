<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        Money::reset();
    }

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge(['category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(), 'price' => 10000, 'stock_quantity' => 5, 'is_active' => true], $o));
    }

    private function enable(string $code, ?float $rate = null): void
    {
        Currency::where('code', $code)->update(['is_active' => true] + ($rate !== null ? ['exchange_rate' => $rate] : []));
        Money::reset();
    }

    public function test_seed_makes_inr_the_only_active_currency_so_the_switcher_stays_hidden_by_default(): void
    {
        $this->assertSame(['INR'], Currency::where('is_active', true)->pluck('code')->all());
        $this->get(route('home'))->assertOk()->assertDontSee('name="currency"', false);
    }

    public function test_prices_are_rupees_by_default(): void
    {
        $this->product();

        $this->get(route('search'))->assertOk()->assertSee('₹10,000')->assertDontSee('≈');
    }

    public function test_switcher_appears_when_another_currency_is_active_and_switching_sets_a_cookie(): void
    {
        $this->enable('USD', 0.0120);

        $this->get(route('home'))->assertOk()->assertSee('name="currency"', false)->assertSee('USD $');
        $this->post(route('currency.switch'), ['currency' => 'usd'])->assertRedirect()->assertCookie(Money::COOKIE, 'USD', false);
    }

    public function test_browse_prices_are_converted_and_always_marked_approximate(): void
    {
        $this->enable('USD', 0.0120);
        $this->product(['price' => 10000, 'sale_price' => 8000]);

        $res = $this->withUnencryptedCookie(Money::COOKIE, 'USD')->get(route('search'));
        $res->assertOk()->assertSee('≈ $96.00')->assertSee('≈ $120')->assertDontSee('₹10,000');
        // The machine-readable config the product page script uses is in sync.
        $res->assertSee('"foreign":true', false)->assertSee('"rate":0.012', false);
    }

    public function test_symbol_position_and_large_amounts_format_correctly(): void
    {
        $this->enable('AED', 0.0440);
        $this->product(['price' => 50000]);

        $this->withUnencryptedCookie(Money::COOKIE, 'AED')->get(route('search'))->assertSee('≈ 2,200 AED');
    }

    public function test_product_page_tells_the_buyer_they_are_charged_in_rupees(): void
    {
        $this->enable('USD', 0.0120);
        $p = $this->product(['price' => 10000]);

        $this->withUnencryptedCookie(Money::COOKIE, 'USD')->get(route('products.show', $p))->assertOk()
            ->assertSee('Approximate price in USD')->assertSee('₹10,000')->assertSee('charged');
        $this->withUnencryptedCookie(Money::COOKIE, 'USD')->get(route('products.quick-view', $p))->assertOk()->assertSee('Approximate. Charged in INR.');
    }

    public function test_cart_and_checkout_stay_in_rupees_with_a_notice_whatever_currency_is_chosen(): void
    {
        $this->enable('USD', 0.0120);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('customer');
        Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
        $p = $this->product(['price' => 10000]);
        $this->actingAs($u)->post(route('customer.cart.store', $p), ['quantity' => 1]);

        foreach ([route('customer.cart.index'), route('customer.checkout.index')] as $url) {
            $this->withUnencryptedCookie(Money::COOKIE, 'USD')->actingAs($u)->get($url)->assertOk()
                ->assertSee('Indian rupees (INR)')->assertSee('₹10,000')->assertDontSee('≈ $');
        }
    }

    public function test_orders_and_totals_are_unaffected_by_the_display_currency(): void
    {
        $this->enable('USD', 0.0120);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('customer');
        $a = Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
        $p = $this->product(['price' => 10000, 'tax_rate' => 12]);
        $this->withUnencryptedCookie(Money::COOKIE, 'USD')->actingAs($u)->post(route('customer.cart.store', $p), ['quantity' => 1]);
        $this->withUnencryptedCookie(Money::COOKIE, 'USD')->actingAs($u)->post(route('customer.checkout.store'), ['shipping_address_id' => $a->id, 'billing_address_id' => $a->id, 'payment_method' => 'cod']);

        $o = Order::firstOrFail();
        $this->assertSame('INR', $o->currency);
        $this->assertEquals(10000, $o->subtotal);
        $this->assertEquals(1200, $o->items()->first()->tax_amount);
    }

    public function test_invalid_inactive_or_zero_rate_currencies_are_refused_and_inr_clears_the_choice(): void
    {
        $this->post(route('currency.switch'), ['currency' => 'USD'])->assertStatus(422); // seeded but inactive
        $this->post(route('currency.switch'), ['currency' => 'XYZ'])->assertStatus(422);
        $this->enable('EUR', 0.0);
        $this->post(route('currency.switch'), ['currency' => 'EUR'])->assertStatus(422);       // a zero rate would show zero prices
        $this->post(route('currency.switch'), ['currency' => ''])->assertSessionHasErrors('currency');
        $this->post(route('currency.switch'), ['currency' => 'INR'])->assertRedirect();
    }

    public function test_a_stale_cookie_for_a_deactivated_currency_falls_back_to_rupees(): void
    {
        $this->product(['price' => 10000]);

        // USD is inactive, but the visitor still carries a USD cookie from earlier.
        $this->withUnencryptedCookie(Money::COOKIE, 'USD')->get(route('search'))->assertOk()->assertSee('₹10,000')->assertDontSee('≈');
    }
}

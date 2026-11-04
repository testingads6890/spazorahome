<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartCheckoutPagesTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('customer');

        return $u;
    }

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x',
            'sku' => 'S-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true,
        ], $o));
    }

    private function address(User $u, array $o = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'Asha', 'last_name' => 'K', 'address_line_1' => '1 Road',
            'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India', 'phone' => '9999999999',
        ], $o));
    }

    public function test_empty_cart_shows_empty_state_and_checkout_redirects_back(): void
    {
        $u = $this->customer();

        $this->actingAs($u)->get(route('customer.cart.index'))->assertOk()->assertSee('Your cart is empty')->assertSee('Start shopping');
        $this->actingAs($u)->get(route('customer.checkout.index'))->assertRedirect(route('customer.cart.index'));
    }

    public function test_cart_shows_lines_variant_label_subtotal_and_free_shipping_progress(): void
    {
        $u = $this->customer();
        $p = $this->product(['price' => 1000]);
        $v = ProductVariant::create(['product_id' => $p->id, 'sku' => 'V1', 'price' => 1500, 'stock_quantity' => 3, 'attributes' => ['color' => 'Grey', 'size' => 'L']]);

        $this->actingAs($u)->post(route('customer.cart.store', [$p, $v]), ['quantity' => 2]);
        $this->actingAs($u)->get(route('customer.cart.index'))->assertOk()
            ->assertSee('Oak Sofa')->assertSee('Grey / L')->assertSee('₹3,000')
            ->assertSee('2 items')->assertSee('more for')->assertSee('₹2,000'); // 5000 free-shipping threshold minus 3000

        $this->actingAs($u)->post(route('customer.cart.store', $this->product(['price' => 3000])), ['quantity' => 1]);
        $this->actingAs($u)->get(route('customer.cart.index'))->assertOk()->assertSee('unlocked')->assertSee('₹6,000');
    }

    public function test_cart_warns_when_quantity_exceeds_stock_and_blocks_checkout_link(): void
    {
        $u = $this->customer();
        $p = $this->product(['stock_quantity' => 5]);
        $this->actingAs($u)->post(route('customer.cart.store', $p), ['quantity' => 4]);
        $p->update(['stock_quantity' => 2]); // stock dropped after adding

        $this->actingAs($u)->get(route('customer.cart.index'))->assertOk()
            ->assertSee('Only 2 in stock')->assertSee('exceed the stock')->assertSee('aria-disabled', false);
    }

    public function test_checkout_shows_saved_addresses_cod_only_when_razorpay_is_not_configured(): void
    {
        config(['services.razorpay.key' => '', 'services.razorpay.secret' => '']);
        $u = $this->customer();
        $this->address($u);
        $this->actingAs($u)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);

        $this->actingAs($u)->get(route('customer.checkout.index'))->assertOk()
            ->assertSee('Delivery address')->assertSee('Asha K')->assertSee('Cash on delivery')->assertDontSee('Pay online');

        // Posting an online payment when it is not configured is rejected.
        $a = Address::first();
        $this->actingAs($u)->post(route('customer.checkout.store'), ['shipping_address_id' => $a->id, 'billing_address_id' => $a->id, 'payment_method' => 'razorpay'])
            ->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_offers_online_payment_when_razorpay_is_configured(): void
    {
        config(['services.razorpay.key' => 'rzp_test', 'services.razorpay.secret' => 'secret']);
        $u = $this->customer();
        $this->address($u);
        $this->actingAs($u)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);

        $this->actingAs($u)->get(route('customer.checkout.index'))->assertOk()->assertSee('Pay online')->assertSee('Cash on delivery');
    }

    public function test_checkout_without_an_address_prompts_to_add_one(): void
    {
        $u = $this->customer();
        $this->actingAs($u)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);

        $this->actingAs($u)->get(route('customer.checkout.index'))->assertOk()->assertSee('Add a delivery address');
    }

    public function test_full_order_flow_from_the_new_checkout_form_places_a_cod_order(): void
    {
        config(['services.razorpay.key' => '', 'services.razorpay.secret' => '']);
        $u = $this->customer();
        $a = $this->address($u);
        $this->actingAs($u)->post(route('customer.cart.store', $this->product(['price' => 1000])), ['quantity' => 1]);

        // Fields exactly as the form submits them (billing mirrors delivery when "same as delivery" is ticked).
        $this->actingAs($u)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $a->id, 'billing_address_id' => $a->id, 'payment_method' => 'cod', 'notes' => 'Leave with security',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('Leave with security', $order->notes);
        $this->assertSame('pending', $order->payments()->first()->status);
    }
}

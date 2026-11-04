<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Delivery options (admin > Shipping > Methods) offered and charged at checkout. */
class ShippingMethodCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
        $this->address = Address::create([
            'user_id' => $this->user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B',
            'address_line_1' => '1 Street', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001',
            'country' => 'IN', 'phone' => '9999999999', 'is_default' => true,
        ]);
    }

    private function cart(float $price = 1000, int $qty = 1): void
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa-' . uniqid(), 'description' => 'x',
            'sku' => 'S-' . uniqid(), 'price' => $price, 'tax_rate' => 18, 'stock_quantity' => 50, 'is_active' => true,
        ]);
        $this->actingAs($this->user)->post(route('customer.cart.store', $product), ['quantity' => $qty]);
    }

    private function checkout(array $extra = [])
    {
        return $this->actingAs($this->user)->post(route('customer.checkout.store'), array_merge([
            'shipping_address_id' => $this->address->id, 'billing_address_id' => $this->address->id, 'payment_method' => 'cod',
        ], $extra));
    }

    public function test_without_delivery_options_checkout_works_as_before(): void
    {
        $this->cart();
        $this->actingAs($this->user)->get(route('customer.checkout.index'))->assertOk()->assertDontSee('name="shipping_method_id"', false);
        $this->checkout()->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertEquals(149, $order->shipping);
        $this->assertNull($order->shipping_method);
    }

    public function test_customer_chooses_a_delivery_option_and_is_charged_for_it(): void
    {
        $standard = ShippingMethod::create(['name' => 'Standard', 'type' => 'flat_rate', 'rate' => 0, 'delivery_estimate' => '5-7 days', 'sort_order' => 1, 'is_active' => true]);
        $express = ShippingMethod::create(['name' => 'Express', 'type' => 'flat_rate', 'rate' => 200, 'sort_order' => 2, 'is_active' => true]);
        ShippingMethod::create(['name' => 'Hidden', 'type' => 'flat_rate', 'rate' => 5, 'is_active' => false]);
        $this->cart();

        $this->actingAs($this->user)->get(route('customer.checkout.index'))->assertOk()
            ->assertSee('Delivery option')->assertSee('Standard')->assertSee('5-7 days')->assertSee('Express')->assertDontSee('Hidden')
            ->assertSee('₹349.00', false); // 149 normal fee + 200 express, shown on the Express card

        // A delivery option is required once any exist, and it must be one on offer.
        $this->checkout()->assertSessionHasErrors('shipping_method_id');
        $this->checkout(['shipping_method_id' => 999])->assertSessionHasErrors('shipping_method_id');
        $this->assertSame(0, Order::count());

        $this->checkout(['shipping_method_id' => $express->id])->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertSame('Express', $order->shipping_method);
        $this->assertEquals(349, $order->shipping);
        $this->assertEquals(62.82, $order->shipping_tax);
        $this->assertEquals(1000 + 180 + 349 + 62.82, $order->total);

        $order->update(['status' => 'shipped']);
        $this->actingAs($this->user)->get(route('customer.orders.invoice', $order))->assertOk()->assertSee('Shipping charges (Express)');
        $this->actingAs($this->user)->get(route('customer.orders.show', $order))->assertOk()->assertSee('Express');
    }

    public function test_free_shipping_needs_its_minimum_order_and_pickup_is_free(): void
    {
        ShippingMethod::create(['name' => 'Standard', 'type' => 'flat_rate', 'rate' => 0, 'sort_order' => 1, 'is_active' => true]);
        $free = ShippingMethod::create(['name' => 'Zero-fee courier', 'type' => 'free_shipping', 'min_order' => 3000, 'sort_order' => 2, 'is_active' => true]);
        $pickup = ShippingMethod::create(['name' => 'Collect from store', 'type' => 'local_pickup', 'sort_order' => 3, 'is_active' => true]);

        $this->cart(1000);
        $this->actingAs($this->user)->get(route('customer.checkout.index'))->assertOk()->assertDontSee('Zero-fee courier')->assertSee('Collect from store');
        $this->checkout(['shipping_method_id' => $free->id])->assertSessionHasErrors('shipping_method_id'); // below the minimum
        $this->checkout(['shipping_method_id' => $pickup->id])->assertSessionHasNoErrors();
        $this->assertEquals(0, Order::first()->shipping);
        $this->assertSame('Collect from store', Order::first()->shipping_method);

        $this->cart(1000, 3);
        $this->actingAs($this->user)->get(route('customer.checkout.index'))->assertOk()->assertSee('Zero-fee courier');
        $this->checkout(['shipping_method_id' => $free->id])->assertSessionHasNoErrors();
        $this->assertEquals(0, Order::latest('id')->first()->shipping);
        $this->assertEquals(3000 + 540, Order::latest('id')->first()->total);
    }

    public function test_admin_can_create_and_edit_a_delivery_option_including_switching_it_off(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.shipping.methods.store'), [
            'name' => 'Express', 'type' => 'flat_rate', 'rate' => 199.5, 'delivery_estimate' => '2 days', 'sort_order' => 2, 'is_active' => 1,
        ])->assertRedirect(route('admin.shipping.methods.index'));
        $method = ShippingMethod::first();
        $this->assertEquals(199.5, $method->rate);
        $this->assertTrue($method->is_active);
        $this->actingAs($admin)->get(route('admin.shipping.methods.index'))->assertOk()->assertSee('Normal fee + ₹199.50', false);
        $this->actingAs($admin)->get(route('admin.shipping.methods.edit', $method))->assertOk()->assertSee('2 days');

        // Unticking "Active" must really switch it off; a non-flat type never keeps an extra charge.
        $this->actingAs($admin)->put(route('admin.shipping.methods.update', $method), ['name' => 'Pickup', 'type' => 'local_pickup', 'rate' => 50])
            ->assertRedirect(route('admin.shipping.methods.index'));
        $method->refresh();
        $this->assertFalse($method->is_active);
        $this->assertEquals(0, $method->rate);

        $this->actingAs($admin)->post(route('admin.shipping.methods.store'), ['name' => 'Bad', 'type' => 'teleport'])->assertSessionHasErrors('type');
    }
}

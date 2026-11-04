<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => 'Test Sofa', 'slug' => 'test-sofa-' . uniqid(),
            'description' => 'Test', 'sku' => 'SKU-' . uniqid(), 'price' => 200, 'stock_quantity' => 5, 'is_active' => true,
        ], $overrides));
    }

    private function address(User $user): Address
    {
        return Address::create([
            'user_id' => $user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B',
            'address_line_1' => '1 Street', 'city' => 'Pune', 'state' => 'MH', 'postal_code' => '411001',
            'country' => 'IN', 'phone' => '9999999999',
        ]);
    }

    public function test_user_cannot_modify_another_users_cart_item(): void
    {
        $owner = $this->customer();
        $attacker = $this->customer();
        $product = $this->product();

        $this->actingAs($owner)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $item = Cart::where('user_id', $owner->id)->first()->items()->first();

        $this->actingAs($attacker)->put(route('customer.cart.update', $item), ['quantity' => 3])->assertNotFound();
        $this->actingAs($attacker)->delete(route('customer.cart.destroy', $item))->assertNotFound();
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_cart_rejects_quantity_above_stock_and_uses_server_price(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 200, 'sale_price' => 150, 'stock_quantity' => 2]);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 3, 'unit_price' => 1])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 2, 'unit_price' => 1]);
        $this->assertEquals(150, Cart::first()->items()->first()->unit_price);
    }

    public function test_cod_checkout_creates_pending_order_and_decrements_stock(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 200, 'stock_quantity' => 5]);
        $address = $this->address($user);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 2]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertEquals(400, $order->subtotal);
        $this->assertEquals(98.82, $order->tax); // 72 item GST + 26.82 GST on the 149 shipping
        $this->assertEquals(647.82, $order->total);
        $this->assertSame('pending', $order->payments()->first()->status);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_checkout_rejects_another_users_address(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $product = $this->product();
        $foreign = $this->address($other);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $foreign->id, 'billing_address_id' => $foreign->id, 'payment_method' => 'cod',
        ])->assertSessionHasErrors('shipping_address_id');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_razorpay_callback_requires_valid_signature(): void
    {
        config(['services.razorpay.key' => 'rzp_test', 'services.razorpay.secret' => 'test-secret']);
        $user = $this->customer();
        $product = $this->product();
        $address = $this->address($user);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'razorpay',
        ]);
        $payment = Order::first()->payments()->first();
        $payment->update(['payment_details' => ['razorpay_order_id' => 'order_X']]);

        $this->actingAs($user)->get(route('payments.razorpay.callback', [
            'razorpay_order_id' => 'order_X', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => 'forged',
        ]))->assertSessionHas('error');
        $this->assertSame('pending', $payment->fresh()->status);

        // The callback double-checks with Razorpay that the payment was really captured for the full amount.
        \Illuminate\Support\Facades\Http::fake(['api.razorpay.com/*' => \Illuminate\Support\Facades\Http::response([
            'id' => 'pay_1', 'order_id' => 'order_X', 'status' => 'captured', 'amount' => (int) round((float) $payment->fresh()->amount * 100), 'currency' => 'INR',
        ])]);
        $signature = hash_hmac('sha256', 'order_X|pay_1', 'test-secret');
        $this->actingAs($user)->get(route('payments.razorpay.callback', [
            'razorpay_order_id' => 'order_X', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => $signature,
        ]))->assertSessionHas('success');
        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('processing', Order::first()->status);
    }

    public function test_vendor_product_checkout_records_commission_and_payout(): void
    {
        $vendorUser = User::factory()->create();
        $vendor = \App\Models\Vendor::create([
            'user_id' => $vendorUser->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'v@example.com',
            'status' => 'approved', 'is_active' => true, 'commission_rate' => 15,
        ]);
        $user = $this->customer();
        $product = $this->product(['price' => 200, 'vendor_id' => $vendor->id, 'vendor_status' => 'approved']);
        $address = $this->address($user);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 2]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod',
        ]);

        $vo = \App\Models\VendorOrder::first();
        $this->assertEquals($vendor->id, $vo->vendor_id);
        $this->assertEquals(400, $vo->subtotal);
        $this->assertEquals(60, $vo->commission);
        $this->assertEquals(340, $vo->payout_amount);
        $this->assertSame('pending', $vo->payout_status);
    }

    private function placeOrder(User $user, Product $product, int $qty, string $method = 'cod'): Order
    {
        $address = $this->address($user);
        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => $qty]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => $method,
        ]);

        return Order::latest('id')->first();
    }

    public function test_customer_cancel_restores_stock_and_closes_payment(): void
    {
        $user = $this->customer();
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder($user, $product, 2);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->actingAs($user)->post(route('customer.orders.cancel', $order))->assertSessionHas('success');
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame('failed', $order->payments()->first()->status);

        // Idempotent: a second cancel must not add stock again.
        $this->actingAs($user)->post(route('customer.orders.cancel', $order))->assertSessionHas('error');
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_customer_cannot_cancel_someone_elses_or_a_shipped_order(): void
    {
        $user = $this->customer();
        $product = $this->product(['stock_quantity' => 5]);
        $order = $this->placeOrder($user, $product, 1);

        $this->actingAs($this->customer())->post(route('customer.orders.cancel', $order))->assertForbidden();

        $order->update(['status' => 'shipped']);
        $this->actingAs($user)->post(route('customer.orders.cancel', $order))->assertSessionHas('error');
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock_quantity);
    }

    public function test_admin_cancel_restores_stock_and_voids_vendor_payout(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $vendor = \App\Models\Vendor::create([
            'user_id' => User::factory()->create()->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'v@example.com',
            'status' => 'approved', 'is_active' => true, 'commission_rate' => 10,
        ]);
        $product = $this->product(['stock_quantity' => 5, 'vendor_id' => $vendor->id, 'vendor_status' => 'approved']);
        $order = $this->placeOrder($this->customer(), $product, 2);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame('failed', \App\Models\VendorOrder::first()->payout_status);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'processing'])->assertSessionHas('error');
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_admin_can_create_shipment_and_customer_can_track_it(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user = $this->customer();
        $order = $this->placeOrder($user, $this->product(), 1);

        $this->actingAs($admin)->get(route('admin.orders.shipping.create', $order))->assertOk();
        $this->actingAs($admin)->post(route('admin.orders.shipping.store', $order), [
            'carrier' => 'BlueDart', 'tracking_number' => 'BD123', 'status' => 'shipped',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('BD123', $order->fresh()->tracking_number);
        $this->actingAs($user)->get(route('customer.order-tracking.track', ['tracking_number' => 'BD123']))->assertOk();
        $this->actingAs($this->customer())->get(route('customer.order-tracking.track', ['tracking_number' => 'BD123']))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('BD123');
    }

    public function test_free_shipping_applies_above_threshold_and_matches_checkout_page(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 6000, 'stock_quantity' => 5]);
        $address = $this->address($user);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk()->assertSee('Free')->assertSee('7,080.00');
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod',
        ]);

        $order = Order::first();
        $this->assertEquals(0, $order->shipping);
        $this->assertEquals(1080, $order->tax);
        $this->assertEquals(7080, $order->total);
    }

    private function zone(array $attrs): \App\Models\ShippingZone
    {
        return \App\Models\ShippingZone::create(array_merge(['name' => 'Z', 'rate' => 100, 'is_active' => true], $attrs));
    }

    public function test_shipping_uses_the_most_specific_matching_zone(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 1000, 'stock_quantity' => 5]);
        $this->zone(['name' => 'India', 'countries' => ['India'], 'rate' => 200, 'free_shipping_threshold' => 8000]);
        $this->zone(['name' => 'Maharashtra', 'countries' => ['India'], 'states' => ['maharashtra'], 'rate' => 80, 'free_shipping_threshold' => 500]);
        $this->zone(['name' => 'Inactive Pune', 'cities' => ['Pune'], 'rate' => 1, 'is_active' => false]);

        $mh = $this->address($user);
        $mh->update(['country' => 'India', 'state' => 'Maharashtra', 'city' => 'Pune']);
        $ka = $this->address($user);
        $ka->update(['country' => 'India', 'state' => 'Karnataka', 'city' => 'Bengaluru']);
        $abroad = $this->address($user);
        $abroad->update(['country' => 'Nepal', 'state' => 'X', 'city' => 'Y']);

        // Maharashtra: free above Rs 500 so the 1000 subtotal ships free. Karnataka: falls back to the India zone (200).
        $this->assertEquals(0, \App\Models\ShippingZone::shippingFor($mh, 1000));
        $this->assertEquals(200, \App\Models\ShippingZone::shippingFor($ka, 1000));
        // No zone covers Nepal, so the store-wide default applies.
        $this->assertEquals(149, \App\Models\ShippingZone::shippingFor($abroad, 1000));

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $ka->id, 'billing_address_id' => $ka->id, 'payment_method' => 'cod',
        ]);
        $order = Order::first();
        $this->assertEquals(200, $order->shipping);
        $this->assertEquals(1000 + 180 + 36 + 200, $order->total); // + 18% GST on the 200 shipping
    }

    public function test_checkout_page_quotes_shipping_per_address(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 1000]);
        $this->zone(['name' => 'India', 'countries' => ['India'], 'rate' => 321]);
        $address = $this->address($user);
        $address->update(['country' => 'India', 'is_default' => true]);

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk()->assertSee('321.00')->assertSee('js-total', false);
    }

    public function test_admin_can_save_shipping_zone_from_the_form(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.shipping.zones.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.shipping.zones.store'), [
            'name' => 'West', 'rate' => 120, 'free_shipping_threshold' => '', 'countries' => 'India',
            'states' => 'Maharashtra, Goa ', 'cities' => '', 'is_active' => 1,
        ])->assertRedirect(route('admin.shipping.zones.index'));

        $zone = \App\Models\ShippingZone::first();
        $this->assertSame(['Maharashtra', 'Goa'], $zone->states);
        $this->assertSame([], $zone->cities);
        $this->assertNull($zone->free_shipping_threshold);
        $this->actingAs($admin)->get(route('admin.shipping.zones.edit', $zone))->assertOk()->assertSee('Maharashtra, Goa', false);
    }

    public function test_razorpay_page_loads_checkout_script_with_server_created_order(): void
    {
        config(['services.razorpay.key' => 'rzp_test_key', 'services.razorpay.secret' => 'secret']);
        \Illuminate\Support\Facades\Http::fake(['api.razorpay.com/*' => \Illuminate\Support\Facades\Http::response(['id' => 'order_ABC'])]);
        $user = $this->customer();
        $order = $this->placeOrder($user, $this->product(['price' => 1000]), 1, 'razorpay');

        $this->actingAs($user)->get(route('payments.razorpay.initiate', $order))
            ->assertOk()
            ->assertSee('checkout.razorpay.com/v1/checkout.js', false)
            ->assertSee('order_ABC', false)
            ->assertSee('rzp_test_key', false);
    }

    public function test_gst_is_calculated_per_product_and_snapshotted_on_the_order(): void
    {
        $user = $this->customer();
        $five = $this->product(['price' => 1000, 'tax_rate' => 5]);
        $twelve = $this->product(['price' => 1000, 'tax_rate' => 12]);
        $default = $this->product(['price' => 1000]);
        $zero = $this->product(['price' => 1000, 'tax_rate' => 0]);
        $address = $this->address($user);

        foreach ([$five, $twelve, $default, $zero] as $p) {
            $this->actingAs($user)->post(route('customer.cart.store', $p), ['quantity' => 1]);
        }

        // 50 + 120 + 180 (store default 18%) + 0
        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk()->assertSee('376.82');
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod',
        ]);

        $order = Order::first();
        $this->assertEquals(350 + 26.82, $order->tax);
        $this->assertEquals(4000 + 350 + 26.82 + 149, $order->total); // subtotal + item GST + shipping GST + shipping
        $this->assertEquals([5.0, 12.0, 18.0, 0.0], $order->items()->orderBy('id')->pluck('tax_rate')->map(fn ($r) => (float) $r)->all());
        $this->assertEquals([50.0, 120.0, 180.0, 0.0], $order->items()->orderBy('id')->pluck('tax_amount')->map(fn ($r) => (float) $r)->all());

        // Changing the product's rate later must not rewrite the past order.
        $five->update(['tax_rate' => 28]);
        $this->assertEquals(50, $order->items()->first()->tax_amount);
    }

    public function test_invoice_splits_gst_into_cgst_sgst_within_state_and_igst_across_states(): void
    {
        config(['shop.seller.state' => 'Maharashtra', 'shop.seller.gstin' => '27ABCDE1234F1Z5']);
        $user = $this->customer();

        $intra = $this->placeOrder($user, $this->product(['price' => 1000, 'tax_rate' => 12]), 1);
        $intra->update(['status' => 'shipped']);
        $intra->shippingAddress->update(['state' => ' maharashtra']);
        // Item GST 120 (60+60) + shipping 149 * 18% = 26.82 (13.41+13.41) -> 73.41 each.
        $this->actingAs($user)->get(route('customer.orders.invoice', $intra))->assertOk()
            ->assertSee('CGST', false)->assertSee('SGST', false)->assertDontSee('IGST', false)
            ->assertSee('27ABCDE1234F1Z5')->assertSee('₹73.41', false)->assertSee('₹146.82', false);

        $inter = $this->placeOrder($user, $this->product(['price' => 1000, 'tax_rate' => 12]), 1);
        $inter->update(['status' => 'shipped']);
        $inter->shippingAddress->update(['state' => 'Karnataka']);
        $this->actingAs($user)->get(route('customer.orders.invoice', $inter))->assertOk()
            ->assertSee('IGST', false)->assertDontSee('SGST', false)->assertSee('₹146.82', false);
    }

    public function test_invoice_is_private_and_only_exists_once_the_order_has_shipped(): void
    {
        $user = $this->customer();
        $order = $this->placeOrder($user, $this->product(), 1);

        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertNotFound();
        $order->update(['status' => 'shipped']);
        $this->actingAs($this->customer())->get(route('customer.orders.invoice', $order))->assertForbidden();
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk();
    }

    public function test_invoice_numbers_are_sequential_unique_and_fit_the_gst_16_character_limit(): void
    {
        $user = $this->customer();
        $a = $this->placeOrder($user, $this->product(), 1);
        $b = $this->placeOrder($user, $this->product(), 1);
        $fy = \App\Models\InvoiceSequence::financialYear(now());

        $b->update(['status' => 'shipped']);   // shipped first, so it gets number 1
        $a->update(['status' => 'delivered']);
        $this->assertSame("INV/{$fy}/000001", $b->fresh()->invoice_number);
        $this->assertSame("INV/{$fy}/000002", $a->fresh()->invoice_number);
        $this->assertLessThanOrEqual(16, strlen($a->fresh()->invoice_number));

        // Re-saving or moving on to delivered never re-numbers.
        $b->update(['status' => 'delivered']);
        $b->fresh()->issueInvoice();
        $this->assertSame("INV/{$fy}/000001", $b->fresh()->invoice_number);
        $this->assertSame(2, \App\Models\InvoiceSequence::first()->last_number);
    }

    public function test_a_rolled_back_transaction_does_not_burn_an_invoice_number(): void
    {
        $fy = \App\Models\InvoiceSequence::financialYear(now());
        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                \App\Models\InvoiceSequence::next(now());
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame("INV/{$fy}/000001", \Illuminate\Support\Facades\DB::transaction(fn () => \App\Models\InvoiceSequence::next(now())));
    }

    public function test_financial_year_runs_april_to_march(): void
    {
        $fy = fn (string $d) => \App\Models\InvoiceSequence::financialYear(\Illuminate\Support\Carbon::parse($d));
        $this->assertSame('25-26', $fy('2026-03-31'));
        $this->assertSame('26-27', $fy('2026-04-01'));
        $this->assertSame('99-00', $fy('2099-12-31'));
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_returning_a_shipped_order_issues_a_credit_note_and_restores_stock(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 1000, 'tax_rate' => 12, 'stock_quantity' => 5]);
        $order = $this->placeOrder($user, $product, 2);
        $order->update(['status' => 'shipped']);
        $invoice = $order->fresh()->invoice_number;
        $fy = \App\Models\InvoiceSequence::financialYear(now());

        // The customer cannot cancel once goods have shipped; an admin handles the return.
        $this->actingAs($user)->post(route('customer.orders.cancel', $order))->assertSessionHas('error');
        $this->assertSame('shipped', $order->fresh()->status);

        $this->actingAs($this->admin())->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $order = $order->fresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame("CN/{$fy}/000001", $order->creditNote->number);
        $this->assertLessThanOrEqual(16, strlen($order->creditNote->number));
        $this->assertSame($invoice, $order->fresh()->invoice_number); // invoice number is never reused or removed

        $this->actingAs($user)->get(route('customer.orders.credit-note', $order))->assertOk()
            ->assertSee('Credit Note')->assertSee("CN/{$fy}/000001")->assertSee("Against invoice {$invoice}")->assertSee('Credit total');
        $this->actingAs($this->customer())->get(route('customer.orders.credit-note', $order))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.orders.credit-note', $order))->assertOk();
        // The original invoice stays viewable, flagged as reversed.
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()->assertSee("CN/{$fy}/000001");
    }

    public function test_cancelling_before_shipment_needs_no_credit_note(): void
    {
        $user = $this->customer();
        $order = $this->placeOrder($user, $this->product(), 1);

        $this->actingAs($user)->post(route('customer.orders.cancel', $order));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNull($order->fresh()->creditNote);
        $this->assertSame(0, \App\Models\CreditNote::count());
        $this->actingAs($user)->get(route('customer.orders.credit-note', $order))->assertNotFound();
    }

    public function test_credit_note_numbers_are_a_separate_gap_free_series_and_never_duplicate(): void
    {
        $user = $this->customer();
        $fy = \App\Models\InvoiceSequence::financialYear(now());
        $numbers = [];
        foreach (range(1, 2) as $i) {
            $o = $this->placeOrder($user, $this->product(), 1);
            $o->update(['status' => 'shipped']);
            $o->cancel(allowAfterShipment: true);
            $numbers[] = [$o->fresh()->invoice_number, $o->fresh()->creditNote->number];
            $this->assertNull($o->fresh()->issueCreditNote('again')); // idempotent
        }

        $this->assertSame([["INV/{$fy}/000001", "CN/{$fy}/000001"], ["INV/{$fy}/000002", "CN/{$fy}/000002"]], $numbers);
        $this->assertSame(2, \App\Models\CreditNote::count());
    }

    public function test_legacy_abbreviated_state_still_resolves_and_unknown_state_is_flagged(): void
    {
        config(['shop.seller.state' => 'Maharashtra']);
        $user = $this->customer();

        $abbr = $this->placeOrder($user, $this->product(['tax_rate' => 12]), 1); // test addresses use "MH"
        $abbr->update(['status' => 'shipped']);
        $this->actingAs($user)->get(route('customer.orders.invoice', $abbr))->assertOk()
            ->assertSee('Maharashtra (27)', false)->assertSee('CGST', false);

        $odd = $this->placeOrder($user, $this->product(['tax_rate' => 12]), 1);
        $odd->shippingAddress->update(['state' => 'Atlantis']);
        $odd->update(['status' => 'shipped']);
        $this->actingAs($user)->get(route('customer.orders.invoice', $odd))->assertOk()
            ->assertSee('unrecognised state', false)->assertSee('IGST', false);
    }

    public function test_invoice_prints_hsn_snapshot_and_sac_for_shipping(): void
    {
        $user = $this->customer();
        $product = $this->product(['price' => 1000, 'hsn_code' => '94036000']);
        $order = $this->placeOrder($user, $product, 1);
        $order->update(['status' => 'shipped']);
        $product->update(['hsn_code' => '99999999']); // later edits must not change the issued invoice

        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()
            ->assertSee('HSN/SAC', false)->assertSee('94036000')->assertSee('996812')->assertDontSee('99999999');
    }

    public function test_invoice_is_emailed_once_when_the_order_ships(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $user = $this->customer();
        $order = $this->placeOrder($user, $this->product(), 1);

        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $order->update(['status' => 'shipped']);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\InvoiceIssued::class, fn ($m) => $m->hasTo($user->email) && $m->order->is($order));

        $order->update(['status' => 'delivered']); // moving on must not resend
        \Illuminate\Support\Facades\Mail::assertSentCount(1);
    }

    public function test_a_mail_failure_does_not_block_shipping(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $order = $this->placeOrder($this->customer(), $this->product(), 1);

        $order->update(['status' => 'shipped']);

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice_number);
    }

    public function test_invoice_email_renders_with_the_gst_breakup(): void
    {
        $order = $this->placeOrder($this->customer(), $this->product(['tax_rate' => 12]), 1);
        $order->update(['status' => 'shipped']);

        $html = (new \App\Mail\InvoiceIssued($order->fresh()))->render();
        $this->assertStringContainsString($order->fresh()->invoice_number, $html);
        $this->assertStringContainsString('CGST', $html);
    }

    public function test_vendor_lines_show_supplier_gstin_and_split_tax_by_the_suppliers_state(): void
    {
        config(['shop.seller.state' => 'Maharashtra', 'shop.seller.gstin' => '27ABCDE1234F1Z5']);
        $vendor = \App\Models\Vendor::create([
            'user_id' => User::factory()->create()->id, 'business_name' => 'Bangalore Woods', 'slug' => 'bw', 'email' => 'bw@example.com',
            'state' => 'Karnataka', 'tax_number' => '29AAAAA0000A1Z5', 'status' => 'approved', 'is_active' => true, 'commission_rate' => 10,
        ]);
        $user = $this->customer();
        $address = $this->address($user);
        $address->update(['state' => 'Maharashtra']);
        foreach ([$this->product(['price' => 1000, 'tax_rate' => 12]), $this->product(['price' => 1000, 'tax_rate' => 12, 'vendor_id' => $vendor->id, 'vendor_status' => 'approved'])] as $p) {
            $this->actingAs($user)->post(route('customer.cart.store', $p), ['quantity' => 1]);
        }
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod',
        ]);
        $order = Order::first();
        $order->update(['status' => 'shipped']);

        // Own product + shipping: Maharashtra -> Maharashtra (CGST+SGST). Vendor product: Karnataka -> Maharashtra (IGST).
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()
            ->assertSee('Supplier: Bangalore Woods', false)->assertSee('GSTIN 29AAAAA0000A1Z5', false)
            ->assertSee('CGST', false)->assertSee('SGST', false)->assertSee('IGST', false)
            ->assertSee('Mixed suppliers', false);
    }
}

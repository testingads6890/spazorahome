<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CouponTest extends TestCase
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
        $cat = Category::firstOrCreate(['slug' => $o['cat'] ?? 'sofas'], ['name' => 'Sofas']);
        unset($o['cat']);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x',
            'sku' => 'S-' . uniqid(), 'price' => 1000, 'stock_quantity' => 10, 'is_active' => true, 'tax_rate' => 10,
        ], $o));
    }

    private function coupon(array $o = []): Discount
    {
        return Discount::create(array_merge(['name' => 'Test', 'code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'is_active' => true], $o));
    }

    private function address(User $u): Address
    {
        return Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
    }

    private function cartWith(User $u, Product $p, int $qty = 1): void
    {
        $this->actingAs($u)->post(route('customer.cart.store', $p), ['quantity' => $qty]);
    }

    private function checkout(User $u, Address $a): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($u)->post(route('customer.checkout.store'), ['shipping_address_id' => $a->id, 'billing_address_id' => $a->id, 'payment_method' => 'cod']);
    }

    public function test_percentage_coupon_reduces_the_taxable_value_and_gst_is_charged_on_the_reduced_amount(): void
    {
        $u = $this->customer();
        $a = $this->address($u);
        $this->coupon(['code' => 'SAVE10', 'value' => 10]);
        $this->cartWith($u, $this->product(['price' => 2000]));

        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => ' save10 '])->assertSessionHas('success');
        $this->actingAs($u)->get(route('customer.checkout.index'))->assertOk()->assertSee('SAVE10')->assertSee('Coupon discount')->assertSee('-₹200.00');
        $this->checkout($u, $a)->assertRedirect();

        $o = Order::firstOrFail();
        $this->assertEquals(2000, $o->subtotal);
        $this->assertEquals(200, $o->discount);
        $this->assertSame('SAVE10', $o->coupon_code);
        // Net 1800 -> item GST 10% = 180; shipping 149 + 18% = 26.82.
        $this->assertEqualsWithDelta(180 + 26.82, (float) $o->tax, 0.001);
        $this->assertEqualsWithDelta(1800 + 180 + 26.82 + 149, (float) $o->total, 0.001);
        $this->assertEquals(200, $o->items()->first()->discount_amount);
        $this->assertEquals(180, $o->items()->first()->tax_amount);
        $this->assertSame(1, Discount::first()->used_count);
        $this->assertNull(session(CouponService::SESSION_KEY)); // a used coupon does not linger in the session
    }

    public function test_fixed_coupon_is_capped_by_the_cart_and_percentage_by_the_max_discount(): void
    {
        $svc = new CouponService();
        $lines = [['product_id' => 1, 'category_id' => 1, 'total' => 300.0]];

        $this->coupon(['code' => 'FLAT500', 'type' => 'fixed', 'value' => 500]);
        $this->assertEquals(300, $svc->apply('FLAT500', $lines)['discount']); // never more than the cart

        $this->coupon(['code' => 'BIG', 'value' => 50, 'max_discount_amount' => 100]);
        $this->assertEquals(100, $svc->apply('BIG', $lines)['discount']);
    }

    public function test_discount_is_split_across_lines_in_proportion_and_always_adds_up(): void
    {
        $this->coupon(['code' => 'ALL', 'type' => 'fixed', 'value' => 100]);
        $lines = [['product_id' => 1, 'category_id' => 1, 'total' => 333.33], ['product_id' => 2, 'category_id' => 1, 'total' => 333.33], ['product_id' => 3, 'category_id' => 1, 'total' => 333.34]];

        $r = (new CouponService())->apply('ALL', $lines);
        $this->assertEquals(100.0, round(array_sum($r['allocations']), 2));
        $this->assertEquals(100.0, $r['discount']);
    }

    public function test_validation_messages_for_unknown_inactive_expired_future_and_limit_and_minimum(): void
    {
        $svc = new CouponService();
        $lines = [['product_id' => 1, 'category_id' => 1, 'total' => 500.0]];

        $this->assertStringContainsString('not valid', $svc->apply('NOPE', $lines)['error']);
        $this->coupon(['code' => 'OFF', 'is_active' => false]);
        $this->assertStringContainsString('not valid', $svc->apply('OFF', $lines)['error']);
        $this->coupon(['code' => 'OLD', 'ends_at' => now()->subDay()]);
        $this->assertStringContainsString('expired', $svc->apply('OLD', $lines)['error']);
        $this->coupon(['code' => 'SOON', 'starts_at' => now()->addDay()]);
        $this->assertStringContainsString('not active yet', $svc->apply('SOON', $lines)['error']);
        $this->coupon(['code' => 'USED', 'usage_limit' => 1, 'used_count' => 1]);
        $this->assertStringContainsString('usage limit', $svc->apply('USED', $lines)['error']);
        $this->coupon(['code' => 'MIN', 'min_order_amount' => 800]);
        $this->assertStringContainsString('₹300 more', $svc->apply('MIN', $lines)['error']);
        $this->assertNull($svc->apply('', $lines)['error']);
    }

    public function test_a_coupon_limited_to_a_category_only_discounts_those_lines(): void
    {
        $u = $this->customer();
        $sofa = $this->product(['price' => 1000, 'cat' => 'sofas']);
        $lamp = $this->product(['price' => 1000, 'cat' => 'lamps', 'name' => 'Lamp']);
        $c = $this->coupon(['code' => 'SOFA20', 'value' => 20]);
        $c->categories()->attach($sofa->category_id);

        $svc = new CouponService();
        $r = $svc->apply('SOFA20', [
            ['product_id' => $sofa->id, 'category_id' => $sofa->category_id, 'total' => 1000.0],
            ['product_id' => $lamp->id, 'category_id' => $lamp->category_id, 'total' => 1000.0],
        ]);
        $this->assertEquals(200, $r['discount']);
        $this->assertSame([200.0, 0.0], $r['allocations']);

        $only = $svc->apply('SOFA20', [['product_id' => $lamp->id, 'category_id' => $lamp->category_id, 'total' => 1000.0]]);
        $this->assertStringContainsString('does not apply', $only['error']);
    }

    public function test_apply_endpoint_rejects_bad_codes_and_an_empty_cart_and_can_remove(): void
    {
        $u = $this->customer();
        $this->coupon(['code' => 'SAVE10']);

        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'SAVE10'])->assertSessionHas('error');
        $this->cartWith($u, $this->product());
        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'WRONG'])->assertSessionHas('coupon_error');
        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'SAVE10'])->assertSessionHas('success');
        $this->actingAs($u)->get(route('customer.cart.index'))->assertSee('SAVE10')->assertSee('applied');
        $this->actingAs($u)->delete(route('customer.coupon.remove'));
        $this->actingAs($u)->get(route('customer.cart.index'))->assertDontSee('applied')->assertSee('Coupon code');
    }

    public function test_a_coupon_that_becomes_invalid_at_checkout_is_dropped_with_a_message_and_no_order_is_placed(): void
    {
        $u = $this->customer();
        $a = $this->address($u);
        $c = $this->coupon(['code' => 'SAVE10']);
        $this->cartWith($u, $this->product());
        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'SAVE10']);

        $c->update(['ends_at' => now()->subMinute()]); // expires between the cart and the click on Place order
        $this->checkout($u, $a)->assertSessionHasErrors('coupon');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $c->fresh()->used_count);
        $this->assertNull(session(CouponService::SESSION_KEY));
    }

    public function test_usage_limit_is_enforced_and_cancelling_gives_the_slot_back(): void
    {
        $c = $this->coupon(['code' => 'ONCE', 'usage_limit' => 1]);
        [$u1, $u2] = [$this->customer(), $this->customer()];
        [$a1, $a2] = [$this->address($u1), $this->address($u2)];

        $this->cartWith($u1, $this->product());
        $this->actingAs($u1)->post(route('customer.coupon.apply'), ['code' => 'ONCE']);
        $this->checkout($u1, $a1)->assertRedirect();
        $this->assertSame(1, $c->fresh()->used_count);

        $this->cartWith($u2, $this->product());
        $this->actingAs($u2)->post(route('customer.coupon.apply'), ['code' => 'ONCE'])->assertSessionHas('coupon_error');

        Order::first()->cancel();
        $this->assertSame(0, $c->fresh()->used_count);
        $this->actingAs($u2)->post(route('customer.coupon.apply'), ['code' => 'ONCE'])->assertSessionHas('success');
    }

    public function test_vendor_commission_and_payout_are_worked_out_on_the_discounted_amount(): void
    {
        $vu = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $vu->id, 'business_name' => 'V', 'slug' => 'v', 'email' => 'v@e.com', 'status' => 'approved', 'is_active' => true, 'commission_rate' => 10]);
        $u = $this->customer();
        $a = $this->address($u);
        $this->coupon(['code' => 'SAVE10', 'value' => 10]);
        $this->cartWith($u, $this->product(['price' => 2000, 'vendor_id' => $vendor->id, 'vendor_status' => 'approved']));
        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'SAVE10']);
        $this->checkout($u, $a);

        $vo = VendorOrder::firstOrFail();
        $this->assertEquals(1800, $vo->subtotal);
        $this->assertEquals(180, $vo->commission);
        $this->assertEquals(1620, $vo->payout_amount);
    }

    public function test_invoice_shows_the_discounted_taxable_value_and_the_coupon(): void
    {
        $u = $this->customer();
        $a = $this->address($u);
        $this->coupon(['code' => 'SAVE10', 'value' => 10]);
        $this->cartWith($u, $this->product(['price' => 2000]));
        $this->actingAs($u)->post(route('customer.coupon.apply'), ['code' => 'SAVE10']);
        $this->checkout($u, $a);

        $order = Order::firstOrFail();
        $order->update(['status' => 'shipped']);
        $this->actingAs($u)->get(route('customer.orders.invoice', $order))->assertOk()
            ->assertSee('₹1,800.00')                      // taxable value after the discount
            ->assertSee('Discount already applied')->assertSee('SAVE10');
        $this->actingAs($u)->get(route('customer.orders.show', $order))->assertSee('Coupon discount')->assertSee('SAVE10');
    }

    public function test_admin_codes_are_stored_upper_case_and_a_percentage_cannot_exceed_100(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.discounts.store'), ['name' => 'Big', 'code' => 'mixed Case', 'type' => 'percentage', 'value' => 150])->assertSessionHasErrors('value');
        $this->actingAs($admin)->post(route('admin.discounts.store'), ['name' => 'Ok', 'code' => 'welcome5', 'type' => 'percentage', 'value' => 5, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('WELCOME5', Discount::firstOrFail()->code);
    }

    public function test_coupon_apply_needs_something_in_the_cart_even_for_a_guest(): void
    {
        // Guests have a cart since guest checkout, so the endpoint is open; with nothing in the cart it does nothing.
        $this->post(route('customer.coupon.apply'), ['code' => 'X'])->assertSessionHas('error');
        $this->assertNull(session(\App\Services\CouponService::SESSION_KEY));
    }
}

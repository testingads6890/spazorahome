<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\CreditNote;
use App\Models\InvoiceSequence;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** SHOP_INVOICE_MODE=per_supplier: every vendor invoices its own goods, and TCS is held back from payouts. */
class SupplierInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function vendor(string $name, string $gstin, string $state): Vendor
    {
        return Vendor::create([
            'user_id' => $this->user('vendor')->id, 'business_name' => $name, 'slug' => strtolower($name), 'email' => strtolower($name) . '@example.com',
            'status' => 'approved', 'is_active' => true, 'commission_rate' => 10, 'tax_number' => $gstin, 'state' => $state, 'city' => 'Jaipur',
        ]);
    }

    private function product(string $name, float $price, ?Vendor $vendor = null): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create([
            'category_id' => $category->id, 'name' => $name, 'slug' => 'p-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(),
            'price' => $price, 'tax_rate' => 18, 'stock_quantity' => 10, 'is_active' => true,
            'vendor_id' => $vendor?->id, 'vendor_status' => $vendor ? 'approved' : null,
        ]);
    }

    /** @param  array<int, array{0: Product, 1: int}>  $lines */
    private function order(User $user, array $lines): Order
    {
        $address = Address::create([
            'user_id' => $user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Street',
            'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'IN', 'phone' => '9999999999',
        ]);
        foreach ($lines as [$product, $qty]) {
            $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => $qty]);
        }
        $this->actingAs($user)->post(route('customer.checkout.store'), ['shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod']);

        return Order::latest('id')->first();
    }

    public function test_by_default_there_is_one_invoice_and_no_tcs(): void
    {
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $order = $this->order($this->user('customer'), [[$this->product('Acme Chair', 1000, $acme), 1]]);
        $order->update(['status' => 'shipped']);

        $this->assertSame(0, SupplierInvoice::count());
        $this->assertStringStartsWith('INV/', $order->fresh()->invoice_number);
        $this->assertEquals(0, VendorOrder::first()->tcs_amount);
        $this->assertEquals(900, VendorOrder::first()->payout_amount);
    }

    public function test_per_supplier_mode_gives_each_supplier_its_own_invoice_in_its_own_series(): void
    {
        config(['shop.invoice_mode' => 'per_supplier', 'shop.seller.state' => 'Maharashtra', 'shop.seller.gstin' => '27ABCDE1234F1Z5', 'shop.seller.name' => 'Interior Furniture']);
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $bolt = $this->vendor('Bolt', '27ZZZZZ9999Z1Z9', 'Maharashtra');
        $user = $this->user('customer');
        $fy = InvoiceSequence::financialYear(now());

        $order = $this->order($user, [[$this->product('Own Lamp', 500), 1], [$this->product('Acme Chair', 1000, $acme), 2], [$this->product('Bolt Table', 1500, $bolt), 1]]);
        $this->assertSame(0, SupplierInvoice::count()); // nothing until the goods ship
        $order->update(['status' => 'shipped']);
        $order = $order->fresh();

        $invoices = $order->supplierInvoices;
        $this->assertSame(["INV/{$fy}/000001", "V{$acme->id}/{$fy}/000001", "V{$bolt->id}/{$fy}/000001"], $invoices->pluck('number')->all());
        $this->assertSame("INV/{$fy}/000001", $order->invoice_number);
        foreach ($invoices as $invoice) {
            $this->assertLessThanOrEqual(16, strlen($invoice->number));
        }
        [$own, $acmeInvoice, $boltInvoice] = $invoices->all();

        // The marketplace's invoice: its own goods and the shipping charge, nothing of the vendors'.
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$order, $own]))->assertOk()
            ->assertSee('Own Lamp')->assertSee('Shipping charges')->assertDontSee('Acme Chair')->assertDontSee('Bolt Table')
            ->assertSee('27ABCDE1234F1Z5')->assertSee('Other invoice of this order');

        // A vendor's invoice: the vendor is the seller, only its lines, tax split by the vendor's own state.
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$order, $acmeInvoice]))->assertOk()
            ->assertSee("V{$acme->id}/{$fy}/000001")->assertSee('Acme Chair')->assertDontSee('Own Lamp')->assertDontSee('Shipping charges')
            ->assertSee('08ABCDE1234F1Z5')->assertSee('Sold through Interior Furniture')
            ->assertSee('IGST', false)->assertDontSee('SGST', false)->assertSee('₹2,360.00', false); // Rajasthan to Maharashtra
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$order, $boltInvoice]))->assertOk()
            ->assertSee('CGST', false)->assertDontSee('IGST', false)->assertSee('₹1,770.00', false);

        // The invoices add up to exactly what the customer paid.
        $builder = app(\App\Services\InvoiceBuilder::class);
        $this->assertEquals((float) $order->total, round($invoices->sum(fn ($i) => $builder->build($order, $i)['grand_total']), 2));

        // The plain invoice address still works, the order pages list all three, and strangers are kept out.
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()->assertSee('Own Lamp')->assertDontSee('Acme Chair');
        $this->actingAs($user)->get(route('customer.orders.show', $order))->assertOk()->assertSee('Acme')->assertSee('Bolt');
        $this->actingAs($this->user('admin'))->get(route('admin.orders.show', $order))->assertOk()->assertSee($acmeInvoice->number)->assertSee($boltInvoice->number);
        $this->actingAs($this->user('customer'))->get(route('customer.orders.invoices.show', [$order, $acmeInvoice]))->assertForbidden();
        $other = $this->order($user, [[$this->product('Own Rug', 100), 1]]);
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$other, $acmeInvoice]))->assertNotFound();

        // The next order continues each series without gaps.
        $next = $this->order($user, [[$this->product('Acme Stool', 200, $acme), 1]]);
        $next->update(['status' => 'shipped']);
        $this->assertSame(["INV/{$fy}/000002", "V{$acme->id}/{$fy}/000002"], $next->fresh()->supplierInvoices->pluck('number')->all()); // the marketplace invoices the shipping charge
    }

    public function test_vendor_sees_only_its_own_invoice(): void
    {
        config(['shop.invoice_mode' => 'per_supplier']);
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $bolt = $this->vendor('Bolt', '27ZZZZZ9999Z1Z9', 'Maharashtra');
        $order = $this->order($this->user('customer'), [[$this->product('Acme Chair', 1000, $acme), 1], [$this->product('Bolt Table', 3000, $bolt), 1]]);
        $acmeOrder = VendorOrder::where('vendor_id', $acme->id)->first();

        $this->actingAs($acme->user)->get(route('vendor.orders.invoice', $acmeOrder))->assertNotFound(); // not shipped yet
        $order->update(['status' => 'shipped']);

        $this->actingAs($acme->user)->get(route('vendor.orders.show', $acmeOrder))->assertOk()->assertSee('Your tax invoice')->assertSee('TCS held back');
        $this->actingAs($acme->user)->get(route('vendor.orders.invoice', $acmeOrder))->assertOk()->assertSee('Acme Chair')->assertDontSee('Bolt Table');
        $this->actingAs($bolt->user)->get(route('vendor.orders.invoice', $acmeOrder))->assertForbidden();
    }

    public function test_tcs_is_held_back_from_the_vendor_payout_and_shrinks_with_a_return(): void
    {
        config(['shop.invoice_mode' => 'per_supplier', 'shop.tcs_rate' => 0.5]);
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $order = $this->order($this->user('customer'), [[$this->product('Acme Chair', 1000, $acme), 4]]);

        $vo = VendorOrder::first();
        $this->assertEquals(4000, $vo->subtotal);
        $this->assertEquals(400, $vo->commission);
        $this->assertEquals(20, $vo->tcs_amount); // 0.5% of the net taxable value
        $this->assertEquals(3580, $vo->payout_amount);

        $order->update(['status' => 'delivered']);
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$order->items()->first()->id => 1], 'restock' => 1]);
        $vo->refresh();
        $this->assertEquals(3000, $vo->subtotal);
        $this->assertEquals(15, $vo->tcs_amount);
        $this->assertEquals(3000 - 300 - 15, $vo->payout_amount);
        $this->actingAs($admin)->get(route('admin.vendor-orders.index'))->assertOk()->assertSee('TCS')->assertSee('₹15.00', false);
    }

    public function test_returns_and_cancellation_issue_a_credit_note_per_supplier_against_its_own_invoice(): void
    {
        config(['shop.invoice_mode' => 'per_supplier']);
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $user = $this->user('customer');
        $fy = InvoiceSequence::financialYear(now());
        $order = $this->order($user, [[$this->product('Own Lamp', 500), 2], [$this->product('Acme Chair', 1000, $acme), 2]]);
        $order->update(['status' => 'delivered']);
        $admin = $this->user('admin');
        $lamp = $order->items()->whereNull('vendor_id')->first();
        $chair = $order->items()->where('vendor_id', $acme->id)->first();
        [$own, $acmeInvoice] = $order->supplierInvoices->all();

        // One return touching both suppliers makes two credit notes, each in its supplier's series.
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$lamp->id => 1, $chair->id => 1], 'restock' => 1])
            ->assertSessionHas('success');
        $notes = CreditNote::orderBy('id')->get();
        $this->assertSame(["CN/{$fy}/000001", "C{$acme->id}/{$fy}/000001"], $notes->pluck('number')->all());
        $this->assertSame([$own->id, $acmeInvoice->id], $notes->pluck('supplier_invoice_id')->all());
        $this->assertEquals(590, $notes[0]->total);
        $this->assertEquals(1180, $notes[1]->total);

        $this->actingAs($user)->get(route('customer.orders.credit-notes.show', [$order, $notes[1]]))->assertOk()
            ->assertSee("Against invoice {$acmeInvoice->number}")->assertSee('Acme Chair')->assertDontSee('Own Lamp')->assertSee('08ABCDE1234F1Z5');
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$order, $acmeInvoice]))->assertOk()
            ->assertSee($notes[1]->number)->assertDontSee($notes[0]->number);

        // Cancelling the rest closes every supplier's invoice; together the notes equal the order total.
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(4, CreditNote::count());
        $this->assertEquals((float) $order->total, round((float) CreditNote::sum('total'), 2));
        $this->assertEquals((float) $order->tax, round((float) CreditNote::sum('tax'), 2));
        $this->assertSame([], array_filter($order->fresh()->returnableQuantities()));
        $closing = CreditNote::where('supplier_invoice_id', $acmeInvoice->id)->where('is_full', true)->first();
        $this->actingAs($user)->get(route('customer.orders.invoices.show', [$order, $acmeInvoice]))->assertOk()->assertSee("Credit note {$closing->number} reverses it");
    }

    public function test_orders_invoiced_before_the_switch_keep_their_single_invoice(): void
    {
        $acme = $this->vendor('Acme', '08ABCDE1234F1Z5', 'Rajasthan');
        $user = $this->user('customer');
        $order = $this->order($user, [[$this->product('Acme Chair', 1000, $acme), 1]]);
        $order->update(['status' => 'shipped']);

        config(['shop.invoice_mode' => 'per_supplier']);
        $order->update(['status' => 'delivered']);
        $this->assertSame(0, SupplierInvoice::count());
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()->assertSee('Supplier: Acme');
        $order->cancel(allowAfterShipment: true);
        $this->assertStringStartsWith('CN/', CreditNote::first()->number);
        $this->assertNull(CreditNote::first()->supplier_invoice_id);
    }
}

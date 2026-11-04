<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\InvoicePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Partial credit notes, Razorpay refunds, cash-on-delivery collection and PDF invoices. */
class ReturnsAndRefundsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => 'Sofa ' . uniqid(), 'slug' => 'sofa-' . uniqid(),
            'description' => 'Test', 'sku' => 'SKU-' . uniqid(), 'price' => 1000, 'tax_rate' => 18, 'stock_quantity' => 10, 'is_active' => true,
        ], $overrides));
    }

    /** @param  array<int, array{0: Product, 1: int}>  $lines */
    private function order(User $user, array $lines, string $method = 'cod'): Order
    {
        $address = Address::create([
            'user_id' => $user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B',
            'address_line_1' => '1 Street', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001',
            'country' => 'IN', 'phone' => '9999999999',
        ]);
        foreach ($lines as [$product, $qty]) {
            $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => $qty]);
        }
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => $method,
        ]);

        return Order::latest('id')->first();
    }

    /** A Razorpay order that the customer has paid for. */
    private function paidOrder(User $user, array $lines): Order
    {
        config(['services.razorpay.key' => 'rzp_test', 'services.razorpay.secret' => 'test-secret']);
        $order = $this->order($user, $lines, 'razorpay');
        $order->payments()->first()->update(['status' => 'completed', 'transaction_id' => 'pay_ABC', 'paid_at' => now()]);
        $order->update(['status' => 'processing']);

        return $order->fresh();
    }

    public function test_partial_return_issues_a_credit_note_for_only_the_returned_items(): void
    {
        $user = $this->user('customer');
        $sofa = $this->product(['name' => 'Oak Sofa', 'price' => 1000, 'tax_rate' => 18, 'stock_quantity' => 10]);
        $lamp = $this->product(['name' => 'Brass Lamp', 'price' => 500, 'tax_rate' => 12, 'stock_quantity' => 10]);
        $order = $this->order($user, [[$sofa, 3], [$lamp, 1]]);
        $order->update(['status' => 'delivered']);
        $fy = \App\Models\InvoiceSequence::financialYear(now());
        $admin = $this->user('admin');
        $sofaLine = $order->items()->where('product_id', $sofa->id)->first();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Return Items');
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), [
            'quantities' => [$sofaLine->id => 1], 'restock' => 1, 'reason' => 'Damaged',
        ])->assertSessionHas('success');

        $note = CreditNote::first();
        $this->assertSame("CN/{$fy}/000001", $note->number);
        $this->assertFalse($note->is_full);
        $this->assertEquals(1180, $note->total); // 1000 + 18% GST, shipping not credited
        $this->assertEquals(180, $note->tax);
        $this->assertSame(8, $sofa->fresh()->stock_quantity); // 10 - 3 sold + 1 back
        $this->assertSame(9, $lamp->fresh()->stock_quantity);
        $this->assertSame('delivered', $order->fresh()->status); // the order itself is not cancelled
        $this->assertNull($order->fresh()->creditNote);

        // The credit note lists only the returned unit; the invoice points at it.
        $this->actingAs($user)->get(route('customer.orders.credit-notes.show', [$order, $note]))->assertOk()
            ->assertSee('Credit Note')->assertSee('Oak Sofa')->assertDontSee('Brass Lamp')->assertSee('₹1,180.00', false)->assertSee('Damaged');
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()->assertSee($note->number)->assertSee('Brass Lamp');
        $this->actingAs($this->user('customer'))->get(route('customer.orders.credit-notes.show', [$order, $note]))->assertForbidden();
        $this->actingAs($user)->get(route('customer.orders.show', $order))->assertOk()->assertSee($order->order_number);
    }

    public function test_return_quantities_cannot_exceed_what_is_left_and_need_a_shipped_order(): void
    {
        $user = $this->user('customer');
        $sofa = $this->product(['stock_quantity' => 10]);
        $order = $this->order($user, [[$sofa, 2]]);
        $admin = $this->user('admin');
        $line = $order->items()->first();

        // Not shipped yet: no invoice, so nothing to credit.
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1]])->assertSessionHas('error');

        $order->update(['status' => 'shipped']);
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 3]])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 0]])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [999999 => 1]])->assertSessionHas('error');
        $this->assertSame(0, CreditNote::count());

        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 2], 'restock' => 1])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1], 'restock' => 1])->assertSessionHas('error');
        $this->assertSame(1, CreditNote::count());
        $this->assertSame(10, $sofa->fresh()->stock_quantity);
    }

    public function test_several_partial_returns_and_a_final_cancel_never_credit_more_than_the_invoice(): void
    {
        $user = $this->user('customer');
        // 3 units at 333.33 with a 7% rate make awkward paise on purpose.
        $item = $this->product(['price' => 333.33, 'tax_rate' => 7, 'stock_quantity' => 10]);
        $order = $this->order($user, [[$item, 3]]);
        $order->update(['status' => 'shipped']);
        $admin = $this->user('admin');
        $line = $order->items()->first();

        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1], 'restock' => 1]);
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1]]); // not restocked
        $this->assertSame(8, $item->fresh()->stock_quantity);

        // Cancelling the rest closes the order: the last unit and the shipping charge are credited, nothing twice.
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $order = $order->fresh();
        $this->assertSame(3, CreditNote::count());
        $this->assertTrue($order->creditNote->is_full);
        $this->assertEquals((float) $order->total, round((float) CreditNote::sum('total'), 2));
        $this->assertEquals((float) $order->tax, round((float) CreditNote::sum('tax'), 2));
        $this->assertSame(9, $item->fresh()->stock_quantity); // only the last unit came back on cancel
        $this->assertSame([], array_filter($order->returnableQuantities()));

        $this->actingAs($admin)->get(route('admin.orders.credit-note', $order))->assertOk()->assertSee('Shipping charges')->assertSee($order->creditNote->number);
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Partial return')->assertSee('Closing');
    }

    public function test_shipping_charge_can_be_credited_once(): void
    {
        $user = $this->user('customer');
        $order = $this->order($user, [[$this->product(['price' => 1000]), 2]]);
        $order->update(['status' => 'shipped']);
        $admin = $this->user('admin');
        $this->assertEquals(149, $order->shipping);

        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['credit_shipping' => 1])->assertSessionHas('success');
        $this->assertEquals(175.82, CreditNote::first()->total); // 149 + 18% GST
        $this->actingAs($admin)->post(route('admin.orders.returns.store', $order), ['credit_shipping' => 1])->assertSessionHas('error');
        $this->assertSame(1, CreditNote::count());
    }

    public function test_partial_return_shrinks_an_unpaid_vendor_payout(): void
    {
        $vendor = Vendor::create([
            'user_id' => User::factory()->create()->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'v@example.com',
            'status' => 'approved', 'is_active' => true, 'commission_rate' => 10,
        ]);
        $user = $this->user('customer');
        $product = $this->product(['price' => 1000, 'vendor_id' => $vendor->id, 'vendor_status' => 'approved']);
        $order = $this->order($user, [[$product, 4]]);
        $order->update(['status' => 'shipped']);
        $line = $order->items()->first();

        $this->actingAs($this->user('admin'))->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1], 'restock' => 1]);

        $vo = VendorOrder::first();
        $this->assertEquals(3000, $vo->subtotal);
        $this->assertEquals(300, $vo->commission);
        $this->assertEquals(2700, $vo->payout_amount);
        $this->assertSame('pending', $vo->payout_status);
    }

    public function test_cancelling_a_paid_razorpay_order_refunds_it_through_the_api(): void
    {
        Http::fake(['api.razorpay.com/v1/payments/pay_ABC/refund' => Http::response(['id' => 'rfnd_1', 'status' => 'processed'], 200)]);
        $user = $this->user('customer');
        $order = $this->paidOrder($user, [[$this->product(['price' => 1000]), 1]]);

        $this->actingAs($user)->post(route('customer.orders.cancel', $order))->assertSessionHas('success');

        $payment = $order->payments()->first();
        $this->assertSame('refunded', $payment->status);
        $this->assertEquals($order->total, $payment->refunded_amount);
        $this->assertEquals(0, $payment->refund_due);
        $this->assertSame('rfnd_1', $payment->payment_details['refunds'][0]['id']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.razorpay.com/v1/payments/pay_ABC/refund'
            && $request['amount'] === (int) round((float) $order->total * 100)
            && $request->hasHeader('Authorization'));
        Http::assertSentCount(1);

        $this->actingAs($user)->get(route('customer.orders.show', $order))->assertOk()->assertSee('Refunded');
    }

    public function test_a_failed_refund_is_kept_as_due_and_can_be_retried_or_marked_by_hand(): void
    {
        Http::fakeSequence('api.razorpay.com/*')
            ->push(['error' => ['description' => 'Insufficient balance']], 400)
            ->push(['id' => 'rfnd_2', 'status' => 'processed'], 200);
        $user = $this->user('customer');
        $order = $this->paidOrder($user, [[$this->product(['price' => 1000]), 1]]);
        $admin = $this->user('admin');

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $payment = $order->payments()->first();
        $this->assertSame('cancelled', $order->fresh()->status); // the gateway failing does not undo the cancellation
        $this->assertEquals(0, $payment->refunded_amount);
        $this->assertEquals($order->total, $payment->refund_due);
        $this->assertSame('pending', $payment->refundState());
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Refund due, not sent yet')->assertSee('Insufficient balance');
        $this->actingAs($user)->get(route('customer.orders.show', $order))->assertOk()->assertSee('Refund in progress');

        $this->actingAs($admin)->post(route('admin.orders.payments.refund', [$order, $payment]))->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals($order->total, $payment->refunded_amount);
        $this->assertEquals(0, $payment->refund_due);
        $this->assertSame('processed', $payment->refundState());

        // Pressing the button again, or marking it by hand afterwards, must not send or book the money twice.
        $this->actingAs($admin)->post(route('admin.orders.payments.refund', [$order, $payment]));
        $this->actingAs($admin)->post(route('admin.orders.payments.refunded', [$order, $payment]));
        Http::assertSentCount(2);
        $this->assertEquals($order->total, $payment->fresh()->refunded_amount);
        $this->assertCount(1, $payment->fresh()->payment_details['refunds']);

        // A payment of another order cannot be touched through this order's URL.
        $other = $this->paidOrder($user, [[$this->product(), 1]]);
        $this->actingAs($admin)->post(route('admin.orders.payments.refund', [$other, $payment]))->assertNotFound();
        $this->actingAs($user)->post(route('admin.orders.payments.refund', [$order, $payment]))->assertForbidden();
    }

    public function test_refund_without_razorpay_keys_is_left_for_the_admin_to_pay_by_hand(): void
    {
        Http::fake();
        $user = $this->user('customer');
        $order = $this->paidOrder($user, [[$this->product(['price' => 1000]), 1]]);
        config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);
        $admin = $this->user('admin');

        $order->cancel();
        $payment = $order->payments()->first();
        Http::assertNothingSent();
        $this->assertEquals($order->total, $payment->refund_due);

        $this->actingAs($admin)->post(route('admin.orders.payments.refunded', [$order, $payment]), ['reference' => 'NEFT-123'])->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals(0, $payment->refund_due);
        $this->assertEquals($order->total, $payment->refunded_amount);
        $this->assertSame('manual', $payment->payment_details['refunds'][0]['status']);
        $this->assertSame('NEFT-123', $payment->payment_details['refunds'][0]['id']);
    }

    public function test_partial_return_refunds_only_the_credited_amount(): void
    {
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'rfnd_3', 'status' => 'processed'], 200)]);
        $user = $this->user('customer');
        $order = $this->paidOrder($user, [[$this->product(['price' => 1000, 'tax_rate' => 18]), 2]]);
        $order->update(['status' => 'delivered']);
        $line = $order->items()->first();

        $this->actingAs($this->user('admin'))->post(route('admin.orders.returns.store', $order), ['quantities' => [$line->id => 1], 'restock' => 1])
            ->assertSessionHas('success');

        $payment = $order->payments()->first();
        $this->assertEquals(1180, $payment->refunded_amount);
        $this->assertSame('completed', $payment->status); // part refund: the payment is not "refunded" as a whole
        Http::assertSent(fn ($request) => $request['amount'] === 118000);

        // Cancelling the rest refunds only what is left.
        $order->cancel(allowAfterShipment: true);
        $payment->refresh();
        $this->assertEquals($order->total, $payment->refunded_amount);
        $this->assertSame('refunded', $payment->status);
    }

    public function test_unpaid_cod_order_has_nothing_to_refund_and_collected_cod_is_refunded_by_hand(): void
    {
        Http::fake();
        $user = $this->user('customer');
        $admin = $this->user('admin');

        $unpaid = $this->order($user, [[$this->product(), 1]]);
        $unpaid->cancel();
        $this->assertSame('failed', $unpaid->payments()->first()->status);
        $this->assertEquals(0, $unpaid->payments()->first()->refund_due);

        $order = $this->order($user, [[$this->product(), 1]]);
        $order->update(['status' => 'delivered']);
        $payment = $order->payments()->first();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Mark cash collected');
        $this->actingAs($admin)->post(route('admin.orders.payments.paid', [$order, $payment]))->assertSessionHas('success');
        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->actingAs($admin)->post(route('admin.orders.payments.paid', [$order, $payment]))->assertSessionHas('error');

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled']);
        $this->assertEquals($order->total, $payment->fresh()->refund_due); // cash cannot be sent back by API
        Http::assertNothingSent();
    }

    public function test_pdf_links_and_attachment_appear_only_when_the_pdf_library_is_installed(): void
    {
        $user = $this->user('customer');
        $order = $this->order($user, [[$this->product(), 1]]);
        $order->update(['status' => 'shipped']);
        $order = $order->fresh();

        // Library missing: no link, the PDF address falls back to the printable page, the email has no attachment.
        $this->mock(InvoicePdf::class, function ($mock) {
            $mock->shouldReceive('available')->andReturn(false);
        });
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()->assertDontSee('Download PDF');
        $this->actingAs($user)->get(route('customer.orders.invoice.pdf', $order))->assertRedirect(route('customer.orders.invoice', $order));
        $this->assertSame([], (new \App\Mail\InvoiceIssued($order))->attachments());

        // Library present.
        $this->mock(InvoicePdf::class, function ($mock) {
            $mock->shouldReceive('available')->andReturn(true);
            $mock->shouldReceive('filename')->andReturn('INV-1.pdf');
            $mock->shouldReceive('render')->andReturn('%PDF-fake');
            $mock->shouldReceive('download')->andReturn(response('%PDF-fake', 200, ['Content-Type' => 'application/pdf']));
        });
        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()
            ->assertSee('Download PDF')->assertSee(route('customer.orders.invoice.pdf', $order), false);
        $this->actingAs($user)->get(route('customer.orders.invoice.pdf', $order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->user('customer'))->get(route('customer.orders.invoice.pdf', $order))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.orders.invoice.pdf', $order))->assertOk();
        $this->assertCount(1, (new \App\Mail\InvoiceIssued($order))->attachments());
        $attachment = (new \App\Mail\InvoiceIssued($order))->attachments()[0];
        $this->assertSame('INV-1.pdf', $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);
        $this->assertStringContainsString('attached as a PDF', (new \App\Mail\InvoiceIssued($order))->render());
    }

    public function test_the_pdf_version_of_the_invoice_view_has_no_buttons_and_uses_a_rupee_capable_font(): void
    {
        $user = $this->user('customer');
        $order = $this->order($user, [[$this->product(), 1]]);
        $order->update(['status' => 'shipped']);
        $order = $order->fresh();

        $html = view('invoices.show', ['order' => $order, 'invoice' => app(\App\Services\InvoiceBuilder::class)->build($order), 'pdf' => true])->render();
        $this->assertStringContainsString('DejaVu Sans', $html);
        $this->assertStringNotContainsString('window.print', $html);
        $this->assertStringNotContainsString('display: flex', $html); // dompdf cannot lay out flexbox
        $this->assertStringContainsString($order->invoice_number, $html);
    }

    public function test_pdf_renders_for_real_when_dompdf_is_installed(): void
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf is not installed (composer require dompdf/dompdf).');
        }

        $user = $this->user('customer');
        $order = $this->order($user, [[$this->product(), 1]]);
        $order->update(['status' => 'shipped']);

        $this->actingAs($user)->get(route('customer.orders.invoice.pdf', $order))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', app(InvoicePdf::class)->render($order->fresh(), app(\App\Services\InvoiceBuilder::class)->build($order->fresh())));
    }
}

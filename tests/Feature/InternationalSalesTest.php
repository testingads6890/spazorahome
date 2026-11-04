<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\InvoiceBuilder;
use App\Services\RefundService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Selling outside India: foreign addresses, zero-rated checkout, foreign-currency payment, export invoice, admin page. */
class InternationalSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['customer', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        config([
            'shop.export.enabled' => true, 'shop.export.lut_number' => 'AD270324000123X', 'shop.export.iec' => 'ABCDE1234F',
            'services.razorpay.key' => 'rzp_test', 'services.razorpay.secret' => 'test-secret',
        ]);
        Money::reset();
    }

    protected function tearDown(): void
    {
        Money::reset();
        parent::tearDown();
    }

    private function user(string $role = 'customer'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(),
            'price' => 1000, 'tax_rate' => 18, 'stock_quantity' => 10, 'is_active' => true, 'export_available' => true,
        ], $overrides));
    }

    private function address(User $user, array $overrides = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $user->id, 'type' => 'shipping', 'first_name' => 'Ann', 'last_name' => 'Lee', 'address_line_1' => '5 High St',
            'city' => 'Austin', 'state' => 'Texas', 'postal_code' => '73301', 'country' => 'United States', 'phone' => '5550100',
        ], $overrides));
    }

    private function zone(array $countries = ['United States'], float $rate = 2500): ShippingZone
    {
        return ShippingZone::create(['name' => 'Abroad', 'countries' => $countries, 'rate' => $rate, 'is_active' => true]);
    }

    private function useCurrency(string $code, float $rate): void
    {
        Currency::where('code', $code)->update(['is_active' => true, 'exchange_rate' => $rate]);
        Money::reset();
    }

    private function checkout(User $user, Address $address, string $method = 'razorpay', array $cookies = [])
    {
        return $this->actingAs($user)->withUnencryptedCookies($cookies)->post(route('customer.checkout.store'), [
            'shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => $method,
        ]);
    }

    public function test_an_address_abroad_takes_a_listed_country_and_a_typed_state(): void
    {
        $user = $this->user();
        $form = ['type' => 'shipping', 'first_name' => 'Ann', 'last_name' => 'Lee', 'address_line_1' => '5 High St', 'city' => 'Austin', 'postal_code' => '73301'];

        $this->actingAs($user)->get(route('customer.addresses.create'))->assertOk()->assertSee('United Kingdom');
        $this->actingAs($user)->post(route('customer.addresses.store'), $form + ['state' => 'Texas', 'country' => 'United States'])->assertSessionHasNoErrors();
        $this->assertTrue(Address::where('country', 'United States')->first()->isForeign());

        $this->actingAs($user)->post(route('customer.addresses.store'), $form + ['state' => 'Texas', 'country' => 'Atlantis'])->assertSessionHasErrors('country');
        // An Indian address still needs a real GST state, and the usual spellings are stored as "India".
        $this->actingAs($user)->post(route('customer.addresses.store'), $form + ['state' => 'Texas', 'country' => 'India'])->assertSessionHasErrors('state');
        $this->actingAs($user)->post(route('customer.addresses.store'), $form + ['state' => 'Maharashtra', 'country' => 'IN'])->assertSessionHasNoErrors();
        $this->assertFalse(Address::where('state', 'Maharashtra')->first()->isForeign());
        $this->assertSame('India', Address::where('state', 'Maharashtra')->value('country'));
    }

    public function test_an_export_order_carries_no_gst_and_is_paid_online_in_rupees_by_default(): void
    {
        $user = $this->user();
        $address = $this->address($user);
        $this->zone();
        $this->actingAs($user)->post(route('customer.cart.store', $this->product()), ['quantity' => 2]);

        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk()->assertSee('exportNote', false);
        $this->checkout($user, $address, 'cod')->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());

        $this->checkout($user, $address)->assertRedirect();
        $order = Order::first();
        $this->assertTrue((bool) $order->is_export);
        $this->assertEquals(0, $order->tax);
        $this->assertEquals(0, $order->shipping_tax);
        $this->assertEquals(2500, $order->shipping);
        $this->assertEquals(4500, $order->total);
        $this->assertEquals(0, $order->items()->first()->tax_rate);
        $this->assertEquals(0, $order->items()->first()->tax_amount);
        $this->assertNull($order->charge_currency);
        $payment = $order->payments()->first();
        $this->assertSame('INR', $payment->currency);
        $this->assertNull($payment->charge_amount);
    }

    public function test_products_not_marked_for_export_and_countries_without_a_zone_are_refused(): void
    {
        $user = $this->user();
        $us = $this->address($user);
        $fr = $this->address($user, ['country' => 'France', 'city' => 'Paris', 'state' => 'Ile-de-France']);
        $this->zone();
        $home = $this->product(['name' => 'Teak Bed', 'export_available' => false]);
        $this->actingAs($user)->post(route('customer.cart.store', $home), ['quantity' => 1]);

        $this->checkout($user, $us)->assertSessionHasErrors('shipping_address_id');
        $this->assertStringContainsString('Teak Bed', session('errors')->first('shipping_address_id'));

        $home->update(['export_available' => true]);
        $this->checkout($user, $fr)->assertSessionHasErrors('shipping_address_id');
        $this->assertStringContainsString('France', session('errors')->first('shipping_address_id'));
        $this->assertSame(0, Order::count());

        $this->checkout($user, $us)->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());
    }

    public function test_abroad_only_courier_delivery_options_can_be_chosen(): void
    {
        $user = $this->user();
        $address = $this->address($user);
        $this->zone();
        $courier = \App\Models\ShippingMethod::create(['name' => 'Express', 'type' => 'flat_rate', 'rate' => 250, 'is_active' => true]);
        $pickup = \App\Models\ShippingMethod::create(['name' => 'Collect', 'type' => 'local_pickup', 'is_active' => true]);
        $free = \App\Models\ShippingMethod::create(['name' => 'Free', 'type' => 'free_shipping', 'min_order' => 0, 'is_active' => true]);
        $this->actingAs($user)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);
        $form = ['shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'razorpay'];

        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk();
        // Collecting in India is not an export, and free delivery is an offer for India.
        $this->actingAs($user)->post(route('customer.checkout.store'), $form + ['shipping_method_id' => $pickup->id])->assertSessionHasErrors('shipping_method_id');
        $this->actingAs($user)->post(route('customer.checkout.store'), $form + ['shipping_method_id' => $free->id])->assertSessionHasErrors('shipping_method_id');
        $this->assertSame(0, Order::count());

        $this->actingAs($user)->post(route('customer.checkout.store'), $form + ['shipping_method_id' => $courier->id])->assertSessionHasNoErrors();
        $this->assertEquals(2750, Order::first()->shipping);
        $this->assertEquals(3750, Order::first()->total);
    }

    public function test_a_product_can_block_countries_and_ask_for_a_minimum_quantity(): void
    {
        $user = $this->user();
        $us = $this->address($user);
        $uk = $this->address($user, ['country' => 'United Kingdom', 'city' => 'London', 'state' => 'Greater London']);
        $this->zone(['United States', 'United Kingdom']);
        $product = $this->product(['name' => 'Brass Lamp', 'export_min_qty' => 3, 'export_blocked_countries' => ['United States']]);
        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 2]);

        $this->checkout($user, $us)->assertSessionHasErrors('shipping_address_id');
        $this->assertStringContainsString('cannot be delivered to United States: Brass Lamp', session('errors')->first('shipping_address_id'));
        $this->checkout($user, $uk)->assertSessionHasErrors('shipping_address_id');
        $this->assertStringContainsString('at least 3', session('errors')->first('shipping_address_id'));
        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk()->assertSee('at least 3');

        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $this->checkout($user, $uk)->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());
    }

    public function test_freight_is_priced_on_packed_weight_and_the_packing_list_shows_it(): void
    {
        $user = $this->user();
        $address = $this->address($user);
        $this->zone()->update(['rate_per_kg' => 400]);
        $product = $this->product(['export_weight_kg' => 12.5, 'hsn_code' => '940360', 'country_of_origin' => 'India']);
        $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 2]);

        $this->checkout($user, $address)->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertEquals(2500 + 400 * 25, $order->shipping); // base rate + 25 kg at 400
        $this->assertEquals(25, $order->export_weight_kg);
        $this->assertEquals(0, $order->tax);

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.orders.packing-list', $order))->assertOk()
            ->assertSee('Packing list (Export)')->assertSee('940360')->assertSee('12.5 kg')->assertSee('25 kg')->assertSee('ABCDE1234F');
        $this->actingAs($user)->get(route('admin.orders.packing-list', $order))->assertForbidden();
        $this->assertSame(25.0, (float) app(InvoiceBuilder::class)->build($order)['export']['weight']);
    }

    public function test_the_admin_product_form_saves_export_details(): void
    {
        $admin = $this->user('admin');
        $product = $this->product(['export_available' => false]);
        $form = $this->dispatchLocation() + ['category_id' => $product->category_id, 'name' => $product->name, 'slug' => $product->slug, 'description' => 'x', 'sku' => $product->sku,
            'price' => 1000, 'stock_quantity' => 5, 'is_active' => 1, 'export_available' => 1, 'export_weight_kg' => '8.25', 'export_min_qty' => '',
            'country_of_origin' => '', 'export_blocked_countries' => ['', 'Canada', 'Japan']];

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Export details');
        $this->actingAs($admin)->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertTrue($product->export_available);
        $this->assertEquals(8.25, $product->export_weight_kg);
        $this->assertSame(1, $product->export_min_qty);
        $this->assertSame('India', $product->country_of_origin);
        $this->assertSame(['Canada', 'Japan'], $product->export_blocked_countries);
        $this->assertFalse($product->exportableTo('japan'));
        $this->assertTrue($product->exportableTo('France'));

        $this->actingAs($admin)->put(route('admin.products.update', $product), ['export_blocked_countries' => ['Atlantis']] + $form)->assertSessionHasErrors('export_blocked_countries.0');
        $this->actingAs($admin)->put(route('admin.products.update', $product), ['export_blocked_countries' => ['']] + $form)->assertSessionHasNoErrors();
        $this->assertSame([], $product->fresh()->export_blocked_countries);
        $this->assertStringContainsString('Packed weight (kg)', $this->actingAs($admin)->get(route('admin.exports.products'))->streamedContent());
    }

    public function test_a_buyer_abroad_can_request_an_export_quote_and_admin_can_answer_it(): void
    {
        $this->zone(['United States', 'France']);
        $product = $this->product(['export_min_qty' => 5, 'export_weight_kg' => 4, 'export_blocked_countries' => ['France']]);
        $home = $this->product(['export_available' => false]);

        $this->get(route('products.show', $product))->assertOk()->assertSee('Request an export quote')->assertSee('Delivered to: United States.')->assertSee('Minimum 5 per international order.');
        $this->get(route('products.show', $home))->assertOk()->assertDontSee('Request an export quote');

        $form = ['name' => 'Ann Lee', 'email' => 'ann@example.com', 'company' => 'Lee Imports', 'country' => 'United States', 'quantity' => 200, 'message' => 'FOB Mundra'];
        $this->post(route('products.export-enquiry', $home), $form)->assertNotFound();
        $this->post(route('products.export-enquiry', $product), ['country' => 'India'] + $form)->assertSessionHasErrorsIn('exportEnquiry', 'country');
        $this->post(route('products.export-enquiry', $product), $form)->assertSessionHas('success');
        $enquiry = \App\Models\ExportEnquiry::first();
        $this->assertSame([200, 'new', $product->id], [$enquiry->quantity, $enquiry->status, $enquiry->product_id]);

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.international.index'))->assertOk()->assertSee('Lee Imports')->assertSee('FOB Mundra');
        $this->actingAs($admin)->put(route('admin.international.enquiries.update', $enquiry), ['status' => 'replied', 'admin_notes' => 'Quoted USD 40'])->assertSessionHas('success');
        $this->assertSame('replied', $enquiry->fresh()->status);
        $this->actingAs($this->user())->put(route('admin.international.enquiries.update', $enquiry), ['status' => 'closed'])->assertForbidden();

        config(['shop.export.enabled' => false]);
        $this->post(route('products.export-enquiry', $product), $form)->assertNotFound();
        $this->get(route('products.show', $product))->assertOk()->assertDontSee('Request an export quote');
    }

    public function test_the_card_is_charged_in_the_chosen_currency_at_the_saved_rate(): void
    {
        $this->useCurrency('USD', 0.012);
        $user = $this->user();
        $address = $this->address($user);
        $this->zone();
        $this->actingAs($user)->post(route('customer.cart.store', $this->product()), ['quantity' => 2]);

        $this->checkout($user, $address, 'razorpay', [Money::COOKIE => 'USD'])->assertRedirect();
        $order = Order::first();
        $this->assertEquals(4500, $order->total); // the books stay in rupees
        $this->assertSame('USD', $order->charge_currency);
        $this->assertEquals(54.00, $order->charge_total);
        $this->assertEquals(0.012, $order->exchange_rate);
        $payment = $order->payments()->first();
        $this->assertSame('USD', $payment->currency);
        $this->assertEquals(54.00, $payment->charge_amount);
        $this->assertEquals(4500, $payment->amount);

        // A later rate change does not touch the order.
        $this->useCurrency('USD', 0.02);
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_X'])]);
        $this->actingAs($user)->get(route('payments.razorpay.initiate', $order))->assertOk()->assertSee('USD 54.00');
        Http::assertSent(fn ($r) => $r['amount'] === 5400 && $r['currency'] === 'USD');

        $this->actingAs($user)->get(route('customer.orders.show', $order))->assertOk()->assertSee('54.00');
    }

    public function test_a_domestic_order_is_never_charged_in_another_currency(): void
    {
        $this->useCurrency('USD', 0.012);
        $user = $this->user();
        $address = $this->address($user, ['country' => 'India', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001']);
        $this->actingAs($user)->post(route('customer.cart.store', $this->product(['export_available' => false])), ['quantity' => 1]);

        $this->checkout($user, $address, 'cod', [Money::COOKIE => 'USD'])->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertFalse((bool) $order->is_export);
        $this->assertEquals(180, $order->tax - $order->shipping_tax);
        $this->assertNull($order->charge_currency);
        $this->assertSame('INR', $order->payments()->first()->currency);
    }

    public function test_refunds_of_a_foreign_currency_payment_go_back_in_that_currency_and_add_up_exactly(): void
    {
        $this->useCurrency('USD', 0.0123);
        $user = $this->user();
        $address = $this->address($user);
        $this->zone(['United States'], 0);
        $this->actingAs($user)->post(route('customer.cart.store', $this->product(['price' => 999])), ['quantity' => 3]);
        $this->checkout($user, $address, 'razorpay', [Money::COOKIE => 'USD']);
        $payment = Order::first()->payments()->first();
        $this->assertEquals(2997, $payment->amount);
        $this->assertEquals(36.86, $payment->charge_amount);
        $payment->update(['status' => 'completed', 'transaction_id' => 'pay_ABC', 'paid_at' => now()]);

        Http::fake(['api.razorpay.com/*' => Http::sequence()->push(['id' => 'rfnd_1', 'status' => 'processed'])->push(['id' => 'rfnd_2', 'status' => 'processed'])]);
        $refunds = app(RefundService::class);
        $this->assertTrue($refunds->refund($payment->fresh(), 999));
        $this->assertTrue($refunds->refund($payment->fresh(), 1998));

        $sent = collect(Http::recorded())->map(fn ($pair) => $pair[0]['amount'])->all();
        $this->assertSame([1229, 2457], $sent); // 12.29 + 24.57 = 36.86, what the card was charged
        $payment->refresh();
        $this->assertEquals(2997, $payment->refunded_amount);
        $this->assertSame('refunded', $payment->status);
        $this->assertSame('USD', $payment->payment_details['refunds'][0]['currency']);
    }

    public function test_the_export_invoice_is_zero_rated_under_the_lut(): void
    {
        $this->useCurrency('USD', 0.012);
        $user = $this->user();
        $address = $this->address($user);
        $this->zone();
        $this->actingAs($user)->post(route('customer.cart.store', $this->product()), ['quantity' => 2]);
        $this->checkout($user, $address, 'razorpay', [Money::COOKIE => 'USD']);
        $order = Order::first();
        $order->payments()->first()->update(['status' => 'completed', 'transaction_id' => 'pay_ABC', 'paid_at' => now()]);
        $order->update(['status' => 'processing']);
        $order->issueInvoice();

        $invoice = app(InvoiceBuilder::class)->build($order->fresh());
        $this->assertSame('Outside India (96): United States', $invoice['place_of_supply']);
        $this->assertSame('AD270324000123X', $invoice['export']['lut']);
        $this->assertEquals(0, $invoice['totals']['igst'] + $invoice['totals']['cgst'] + $invoice['totals']['sgst']);

        $this->actingAs($user)->get(route('customer.orders.invoice', $order))->assertOk()
            ->assertSee('(Export)')->assertSee('AD270324000123X')->assertSee('ABCDE1234F')->assertSee('Outside India (96)');
    }

    public function test_a_visitor_abroad_can_check_out_as_a_guest(): void
    {
        $this->zone(['United Kingdom']);
        $this->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);
        $this->get(route('checkout.guest'))->assertOk()->assertSee('United Kingdom');

        $this->post(route('checkout.guest.store'), [
            'first_name' => 'Tom', 'last_name' => 'Hill', 'email' => 'tom@example.com', 'phone' => '7700900000', 'address_line_1' => '1 Baker St',
            'city' => 'London', 'state' => 'Greater London', 'postal_code' => 'NW1 6XE', 'country' => 'United Kingdom',
        ])->assertRedirect(route('customer.checkout.index'));

        $address = Address::first();
        $this->assertSame('United Kingdom', $address->country);
        $this->checkout(User::where('email', 'tom@example.com')->first(), $address)->assertSessionHasNoErrors();
        $this->assertTrue((bool) Order::first()->is_export);
    }

    public function test_admin_sees_the_setup_and_can_mark_all_products_for_export(): void
    {
        $admin = $this->user('admin');
        $a = $this->product(['export_available' => false]);
        $b = $this->product(['export_available' => false, 'is_active' => false]);

        $this->actingAs($admin)->get(route('admin.international.index'))->assertOk()->assertSee('Letter of Undertaking')->assertSee('Shipping zone for at least one other country');
        $this->actingAs($admin)->post(route('admin.international.products'), ['available' => 1])->assertSessionHas('success');
        $this->assertTrue((bool) $a->fresh()->export_available);
        $this->assertFalse((bool) $b->fresh()->export_available); // inactive products are left alone
        $this->actingAs($admin)->post(route('admin.international.products'), ['available' => 0]);
        $this->assertFalse((bool) $a->fresh()->export_available);

        $this->actingAs($this->user())->get(route('admin.international.index'))->assertForbidden();
    }

    public function test_export_orders_show_on_the_admin_page_and_in_the_orders_csv(): void
    {
        $this->useCurrency('USD', 0.012);
        $user = $this->user();
        $address = $this->address($user);
        $this->zone();
        $this->actingAs($user)->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);
        $this->checkout($user, $address, 'razorpay', [Money::COOKIE => 'USD']);
        $order = Order::first();
        Money::reset();

        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.international.index'))->assertOk()->assertSee($order->order_number)->assertSee('United States');
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('USD');
        $csv = $this->actingAs($admin)->get(route('admin.exports.orders'))->streamedContent();
        $this->assertStringContainsString('Charged currency', $csv);
        $this->assertStringContainsString('USD', $csv);
    }

    public function test_with_international_selling_off_nothing_changes(): void
    {
        config(['shop.export.enabled' => false]);
        $user = $this->user();
        // Addresses saved before the switch existed may hold any country text; all are treated as Indian.
        $address = $this->address($user, ['state' => 'Maharashtra', 'country' => 'United States']);
        $this->assertFalse($address->isForeign());
        $this->actingAs($user)->post(route('customer.cart.store', $this->product(['export_available' => false])), ['quantity' => 1]);

        $this->actingAs($user)->get(route('customer.checkout.index'))->assertOk();
        $this->actingAs($user)->get(route('customer.addresses.create'))->assertOk()->assertDontSee('United Kingdom');
        $this->checkout($user, $address, 'cod')->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertFalse((bool) $order->is_export);
        $this->assertEquals(180, $order->tax - $order->shipping_tax);
    }
}

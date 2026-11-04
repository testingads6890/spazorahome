<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CSV exports for the admin and for sellers, and the report pages that link to them. */
class DataExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Vendor $vendor;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        config(['shop.seller.state' => 'Maharashtra', 'shop.seller.gstin' => '27ABCDE1234F1Z5', 'shop.seller.name' => 'Interior Furniture']);
        $this->admin = $this->user('admin');
        $this->customer = $this->user('customer', ['name' => '=HYPERLINK("http://evil")', 'email' => 'asha@example.com']);
        $this->vendor = Vendor::create([
            'user_id' => $this->user('vendor')->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'acme@example.com',
            'status' => 'approved', 'is_active' => true, 'commission_rate' => 10, 'tax_number' => '08ABCDE1234F1Z5', 'state' => 'Rajasthan',
        ]);

        $address = Address::create([
            'user_id' => $this->customer->id, 'type' => 'shipping', 'first_name' => 'Asha', 'last_name' => 'Mehta', 'address_line_1' => '1 Street',
            'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India', 'phone' => '9999999999',
        ]);
        $this->actingAs($this->customer)->post(route('customer.cart.store', $this->product('Own Lamp, brass', 500, '94051000')), ['quantity' => 2]);
        $this->actingAs($this->customer)->post(route('customer.cart.store', $this->product('Acme Chair', 1000, '94017100', $this->vendor)), ['quantity' => 1]);
        $this->actingAs($this->customer)->post(route('customer.checkout.store'), ['shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod']);
        $this->order = Order::first();
    }

    private function user(string $role, array $attrs = []): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create($attrs);
        $user->assignRole($role);

        return $user;
    }

    private function product(string $name, float $price, string $hsn, ?Vendor $vendor = null): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create([
            'category_id' => $category->id, 'name' => $name, 'slug' => 'p-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(), 'price' => $price,
            'tax_rate' => 18, 'hsn_code' => $hsn, 'stock_quantity' => 10, 'is_active' => true, 'vendor_id' => $vendor?->id, 'vendor_status' => $vendor ? 'approved' : null,
        ]);
    }

    /** @return array<int, array<int, string>> parsed CSV rows, heading first */
    private function csv(string $route, array $query = [], ?User $as = null): array
    {
        $response = $this->actingAs($as ?? $this->admin)->get(route($route, $query))->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content); // Excel needs the mark to read UTF-8

        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, substr($content, 3));
        rewind($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function test_exports_are_admin_only_and_listed_on_one_page(): void
    {
        $names = array_keys(\App\Http\Controllers\Admin\ExportController::catalogue());
        $this->assertCount(9, $names);
        auth()->logout();
        foreach ($names as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
        foreach ($names as $name) {
            $this->actingAs($this->customer)->get(route($name))->assertForbidden();
        }

        $page = $this->actingAs($this->admin)->get(route('admin.exports.index'))->assertOk()->assertSee('GST sales register');
        foreach ($names as $name) {
            $page->assertSee(route($name), false);
        }
    }

    public function test_orders_export_has_one_row_per_order_and_respects_filters(): void
    {
        $rows = $this->csv('admin.exports.orders');
        $this->assertCount(2, $rows);
        $row = array_combine($rows[0], $rows[1]);
        $this->assertSame($this->order->order_number, $row['Order number']);
        $this->assertSame('asha@example.com', $row['Email']);
        $this->assertSame('Pune', $row['City']);
        $this->assertSame('2', $row['Items']);
        $this->assertSame('2000.00', $row['Subtotal']);
        $this->assertSame(number_format((float) $this->order->total, 2, '.', ''), $row['Total']);
        $this->assertSame('cod', $row['Payment method']);
        // A name typed to look like a spreadsheet formula is defused, not run.
        $this->assertSame('\'=HYPERLINK("http://evil")', $row['Customer']);

        $this->assertCount(1, $this->csv('admin.exports.orders', ['status' => 'shipped']));
        $this->assertCount(2, $this->csv('admin.exports.orders', ['status' => 'pending']));
        $this->assertCount(1, $this->csv('admin.exports.orders', ['to' => now()->subDay()->toDateString()]));
        $this->assertCount(2, $this->csv('admin.exports.orders', ['from' => now()->toDateString(), 'to' => now()->toDateString()]));
        $this->actingAs($this->admin)->get(route('admin.exports.orders', ['from' => '2026-05-10', 'to' => '2026-05-01']))->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'pending']))->assertOk()->assertSee('Export CSV')->assertSee('status=pending', false);
    }

    public function test_gst_sales_register_lists_every_invoice_line_with_the_tax_split(): void
    {
        $this->assertCount(1, $this->csv('admin.exports.sales-register')); // nothing invoiced until the order ships
        $this->order->update(['status' => 'shipped']);
        $invoice = $this->order->fresh()->invoice_number;

        $rows = $this->csv('admin.exports.sales-register');
        $this->assertCount(4, $rows); // lamp, chair, shipping
        $lines = collect(array_slice($rows, 1))->map(fn ($r) => array_combine($rows[0], $r))->keyBy('Item');

        $lamp = $lines['Own Lamp, brass']; // a comma in the name stays in one cell
        $this->assertSame($invoice, $lamp['Invoice number']);
        $this->assertSame('94051000', $lamp['HSN/SAC']);
        $this->assertSame('1000.00', $lamp['Taxable value']);
        $this->assertSame(['90.00', '90.00', '0.00'], [$lamp['CGST'], $lamp['SGST'], $lamp['IGST']]); // Maharashtra to Maharashtra
        $this->assertSame('27ABCDE1234F1Z5', $lamp['Seller GSTIN']);

        $chair = $lines['Acme Chair']; // supplied from Rajasthan, so IGST
        $this->assertSame(['0.00', '0.00', '180.00'], [$chair['CGST'], $chair['SGST'], $chair['IGST']]);
        $this->assertSame('Acme', $chair['Seller']);
        $this->assertSame('08ABCDE1234F1Z5', $chair['Seller GSTIN']);
        $this->assertSame('996812', $lines['Shipping charges']['HSN/SAC']);

        // The register adds up to the invoice.
        $this->assertEquals((float) $this->order->total, round($lines->sum(fn ($l) => (float) $l['Line total']), 2));
        $this->assertCount(1, $this->csv('admin.exports.sales-register', ['to' => now()->subDay()->toDateString()]));
    }

    public function test_sales_register_follows_per_supplier_invoices_and_credit_notes_have_their_own_register(): void
    {
        config(['shop.invoice_mode' => 'per_supplier']);
        $this->order->update(['status' => 'delivered']);
        $order = $this->order->fresh();
        $numbers = $order->supplierInvoices->pluck('number')->all();

        $rows = $this->csv('admin.exports.sales-register');
        $byItem = collect(array_slice($rows, 1))->map(fn ($r) => array_combine($rows[0], $r))->keyBy('Item');
        $this->assertSame($numbers[0], $byItem['Own Lamp, brass']['Invoice number']);
        $this->assertSame($numbers[1], $byItem['Acme Chair']['Invoice number']);
        $this->assertSame('Acme', $byItem['Acme Chair']['Seller']);
        $this->assertSame('Interior Furniture', $byItem['Shipping charges']['Seller']);

        $this->assertCount(1, $this->csv('admin.exports.credit-notes'));
        $chair = $order->items()->where('vendor_id', $this->vendor->id)->first();
        $this->actingAs($this->admin)->post(route('admin.orders.returns.store', $order), ['quantities' => [$chair->id => 1], 'restock' => 1, 'reason' => 'Damaged']);

        $rows = $this->csv('admin.exports.credit-notes');
        $this->assertCount(2, $rows);
        $note = array_combine($rows[0], $rows[1]);
        $this->assertSame("C{$this->vendor->id}/" . \App\Models\InvoiceSequence::financialYear(now()) . '/000001', $note['Credit note number']);
        $this->assertSame($numbers[1], $note['Against invoice']);
        $this->assertSame('Damaged', $note['Reason']);
        $this->assertSame('1180.00', $note['Line total']);
    }

    public function test_customer_product_vendor_and_day_exports_carry_the_right_figures(): void
    {
        $customers = $this->csv('admin.exports.customers');
        $this->assertCount(2, $customers); // the admin and the seller are not customers
        $row = array_combine($customers[0], $customers[1]);
        $this->assertSame(['asha@example.com', '1', number_format((float) $this->order->total, 2, '.', '')], [$row['Email'], $row['Orders'], $row['Spent']]);

        $products = $this->csv('admin.exports.products');
        $this->assertCount(3, $products);
        $chair = collect(array_slice($products, 1))->map(fn ($r) => array_combine($products[0], $r))->firstWhere('Name', 'Acme Chair');
        $this->assertSame(['Acme', '1000.00', '94017100', '9', 'approved'], [$chair['Seller'], $chair['Price'], $chair['HSN code'], $chair['Stock'], $chair['Seller listing status']]);

        $vendors = $this->csv('admin.exports.vendors');
        $acme = array_combine($vendors[0], $vendors[1]);
        $this->assertSame(['Acme', '08ABCDE1234F1Z5', '1000.00', '100.00', '900.00', '0.00'], [$acme['Business'], $acme['GSTIN'], $acme['Sales'], $acme['Commission'], $acme['Payout due'], $acme['Payout paid']]);

        $lines = $this->csv('admin.exports.vendor-orders');
        $this->assertCount(2, $lines);
        $this->assertSame('900.00', array_combine($lines[0], $lines[1])['Payout']);
        $this->assertCount(1, $this->csv('admin.exports.vendor-orders', ['payout_status' => 'paid']));
        $this->assertCount(1, $this->csv('admin.exports.vendor-payouts'));

        $days = $this->csv('admin.exports.sales-by-day');
        $today = array_combine($days[0], $days[1]);
        $this->assertSame([now()->toDateString(), '1', number_format((float) $this->order->total, 2, '.', '')], [$today['Date'], $today['Orders'], $today['Total']]);
        $this->order->cancel();
        $this->assertCount(1, $this->csv('admin.exports.sales-by-day')); // cancelled orders are not sales
        $this->assertSame('0.00', array_combine($customers[0], $this->csv('admin.exports.customers')[1])['Spent']);
    }

    public function test_a_seller_exports_only_their_own_order_lines(): void
    {
        $other = Vendor::create(['user_id' => $this->user('vendor')->id, 'business_name' => 'Bolt', 'slug' => 'bolt', 'email' => 'bolt@example.com', 'status' => 'approved', 'is_active' => true]);

        $rows = $this->csv('vendor.orders.export', [], $this->vendor->user);
        $this->assertCount(2, $rows);
        $this->assertSame(['Acme Chair', '900.00'], [array_combine($rows[0], $rows[1])['Product'], array_combine($rows[0], $rows[1])['Payout']]);
        $this->assertCount(1, $this->csv('vendor.orders.export', [], $other->user));
        $this->actingAs($this->vendor->user)->get(route('vendor.orders.index'))->assertOk()->assertSee(route('vendor.orders.export'), false);
        $this->actingAs($this->customer)->get(route('vendor.orders.export'))->assertForbidden();
    }

    public function test_report_pages_show_real_figures_and_link_to_their_exports(): void
    {
        $total = number_format((float) $this->order->total, 2);

        $this->actingAs($this->admin)->get(route('admin.reports.sales'))->assertOk()
            ->assertSee('₹' . $total, false)->assertSee('salesChart')->assertSee(e(route('admin.exports.sales-by-day', ['from' => now()->subDays(29)->toDateString(), 'to' => now()->toDateString()])), false);
        $this->actingAs($this->admin)->get(route('admin.reports.sales', ['from' => now()->subDays(9)->toDateString(), 'to' => now()->subDays(2)->toDateString()]))->assertOk()
            ->assertSee('No orders in this period.');
        $this->actingAs($this->admin)->get(route('admin.reports.products'))->assertOk()->assertSee('Own Lamp, brass')->assertSee('2 units');
        $this->actingAs($this->admin)->get(route('admin.reports.customers'))->assertOk()->assertSee('asha@example.com')->assertSee('₹' . $total, false);
        $this->actingAs($this->admin)->get(route('admin.reports.vendors'))->assertOk()->assertSee('Acme')->assertSee('₹1,000.00', false);
        $this->actingAs($this->admin)->get(route('admin.reports.support'))->assertOk()->assertSee('No tickets yet.');
    }

    public function test_csv_cells_are_safe_and_numbers_stay_numbers(): void
    {
        $this->assertSame("'=1+1", CsvExport::cell('=1+1'));
        $this->assertSame("'+91 99999", CsvExport::cell('+91 99999'));
        $this->assertSame("'@cmd", CsvExport::cell('@cmd'));
        $this->assertSame('-250.50', CsvExport::cell('-250.50')); // a negative amount is not a formula
        $this->assertSame('9999999999', CsvExport::cell('9999999999'));
        $this->assertSame('yes', CsvExport::cell(true));
        $this->assertSame('', CsvExport::cell(null));
        $this->assertSame('1234.50', CsvExport::money(1234.5));
    }
}

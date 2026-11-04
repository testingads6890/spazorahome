<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use App\Models\VendorWalletTransaction;
use App\Models\VendorWithdrawal;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The seller wallet: earnings, the hold period, refunds, withdrawals and the team's manual corrections. */
class VendorWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Vendor $vendor;

    private User $admin;

    private Product $product;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['vendor', 'customer', 'admin', 'vendor_manager'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->owner = User::factory()->create();
        $this->owner->assignRole('vendor');
        $this->vendor = Vendor::create([
            'user_id' => $this->owner->id, 'business_name' => 'Acme Furniture', 'slug' => 'acme', 'email' => 'a@e.com',
            'status' => 'approved', 'is_active' => true,
            'bank_details' => ['account_holder' => 'Acme', 'bank_name' => 'HDFC', 'account_number' => '1234567890', 'ifsc' => 'HDFC0001234', 'upi' => 'acme@upi'],
        ]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $this->product = Product::create(['category_id' => $category->id, 'vendor_id' => $this->vendor->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x', 'sku' => 'S1', 'price' => 10, 'stock_quantity' => 5, 'is_active' => true]);
    }

    /** One order line worth $payout to the seller, in the given state. */
    private function line(float $payout = 1000, string $status = 'pending', string $orderStatus = 'processing'): VendorOrder
    {
        $this->n++;
        $order = Order::create(['user_id' => User::factory()->create()->id, 'order_number' => 'ORD-W' . $this->n, 'status' => $orderStatus, 'subtotal' => $payout, 'total' => $payout, 'currency' => 'INR']);
        $item = $order->items()->create(['product_id' => $this->product->id, 'vendor_id' => $this->vendor->id, 'product_name' => 'Sofa', 'product_sku' => 'S1', 'quantity' => 1, 'unit_price' => $payout, 'subtotal' => $payout]);

        return VendorOrder::create([
            'vendor_id' => $this->vendor->id, 'order_id' => $order->id, 'order_item_id' => $item->id,
            'subtotal' => $payout + 100, 'commission' => 100, 'payout_amount' => $payout, 'payout_status' => 'pending', 'status' => $status,
        ]);
    }

    private function money(): array
    {
        return app(WalletService::class)->summary($this->vendor);
    }

    public function test_money_is_earned_when_a_line_is_delivered_and_only_once(): void
    {
        $line = $this->line(1000, 'shipped');
        $this->assertSame(0.0, $this->money()['balance'], 'a line that is not delivered earns nothing');

        $line->update(['status' => 'delivered']);
        $this->assertSame(1000.0, $this->money()['balance']);
        $this->assertSame(1000.0, $this->money()['available']);
        $this->assertNotNull($line->fresh()->delivered_at);

        $line->update(['status' => 'delivered']);
        $line->touch();
        app(WalletService::class)->syncVendorOrder($line->fresh());
        $this->assertSame(1, VendorWalletTransaction::where('vendor_id', $this->vendor->id)->count(), 'syncing again must not credit twice');
    }

    public function test_earnings_stay_on_hold_for_the_configured_days(): void
    {
        config(['shop.wallet.hold_days' => 7]);
        $this->line(1000, 'delivered');

        $m = $this->money();
        $this->assertSame(1000.0, $m['balance']);
        $this->assertSame(1000.0, $m['held']);
        $this->assertSame(0.0, $m['available']);
        $this->assertNotNull($m['next_release']);

        $this->travel(8)->days();
        $m = $this->money();
        $this->assertSame(0.0, $m['held']);
        $this->assertSame(1000.0, $m['available']);
    }

    public function test_partial_refund_and_cancellation_take_earnings_back(): void
    {
        $line = $this->line(1000, 'delivered', 'shipped');
        $this->assertSame(1000.0, $this->money()['balance']);

        // What returnItems() does for a partial return: the line's payout shrinks.
        $line->update(['subtotal' => 600, 'commission' => 60, 'payout_amount' => 540]);
        $this->assertSame(540.0, $this->money()['balance']);

        // Whole order cancelled after delivery (admin override): the line fails in bulk and the rest is taken back.
        $this->assertTrue(Order::find($line->order_id)->cancel(true));
        $this->assertSame(0.0, $this->money()['balance']);
        $this->assertSame(['order', 'order_adjustment', 'order_adjustment'], VendorWalletTransaction::orderBy('id')->pluck('kind')->all());
    }

    public function test_withdrawal_takes_money_out_at_once_and_a_rejection_gives_it_back(): void
    {
        config(['shop.withdrawals.minimum' => 500]);
        $this->line(1500, 'delivered');

        $this->actingAs($this->owner)->get(route('vendor.withdrawals.index'))->assertOk();
        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 2000, 'method' => 'upi'])->assertSessionHasErrors('amount');
        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 100, 'method' => 'upi'])->assertSessionHasErrors('amount');
        $this->assertSame(0, VendorWithdrawal::count());

        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 1000, 'method' => 'upi'])->assertSessionHasNoErrors();
        $w = VendorWithdrawal::first();
        $this->assertSame(500.0, $this->money()['available'], 'the requested amount has left the wallet');

        // The same money cannot be asked for twice.
        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 1000, 'method' => 'upi'])->assertSessionHasErrors('amount');
        $this->assertSame(1, VendorWithdrawal::count());

        $this->actingAs($this->admin)->post(route('admin.vendor-withdrawals.reject', $w), ['admin_note' => 'Bank details look wrong'])->assertSessionHasNoErrors();
        $this->assertSame(1500.0, $this->money()['available'], 'rejected: back in the wallet');

        // Rejecting again (or a double click) must not give it back twice.
        app(WalletService::class)->releaseWithdrawal($w->fresh(), 'rejected');
        $this->assertSame(1500.0, $this->money()['available']);
    }

    public function test_cancelling_a_request_returns_the_money_and_paid_marks_the_orders(): void
    {
        $a = $this->line(700, 'delivered');
        $b = $this->line(800, 'delivered');
        $this->line(300, 'delivered');

        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 1500, 'method' => 'bank_transfer']);
        $first = VendorWithdrawal::first();
        $this->assertSame(300.0, $this->money()['available']);
        $this->actingAs($this->owner)->post(route('vendor.withdrawals.cancel', $first))->assertSessionHasNoErrors();
        $this->assertSame(1800.0, $this->money()['available']);

        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 1500, 'method' => 'bank_transfer']);
        $second = VendorWithdrawal::where('status', 'pending')->first();
        $this->actingAs($this->admin)->post(route('admin.vendor-withdrawals.approve', $second))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.vendor-withdrawals.paid', $second), ['transaction_ref' => 'UTR123'])->assertSessionHasNoErrors();

        $second->refresh();
        $this->assertSame('paid', $second->status);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $second->vendor_order_ids);
        $this->assertSame('paid', $a->fresh()->payout_status);
        $this->assertSame(300.0, $this->money()['balance'], 'paying out does not touch the wallet a second time');
        $this->assertSame(1500.0, $this->money()['withdrawn']);
    }

    public function test_marking_a_line_paid_or_completing_a_payout_leaves_the_wallet_once(): void
    {
        $line = $this->line(900, 'delivered');
        $this->actingAs($this->admin)->post(route('admin.vendor-orders.markPaid', $line));
        $this->actingAs($this->admin)->post(route('admin.vendor-orders.markPaid', $line));
        $this->assertSame(0.0, $this->money()['balance']);

        $this->line(400, 'delivered');
        $payout = VendorPayout::create(['vendor_id' => $this->vendor->id, 'amount' => 400, 'method' => 'bank', 'status' => 'pending']);
        $this->actingAs($this->admin)->post(route('admin.vendor-payouts.complete', $payout));
        $this->actingAs($this->admin)->post(route('admin.vendor-payouts.complete', $payout));
        $this->assertSame(0.0, $this->money()['balance']);
    }

    public function test_only_the_admin_role_can_adjust_and_a_reason_is_needed(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('vendor_manager');
        $url = route('admin.vendor-wallets.adjust', $this->vendor);

        $this->actingAs($manager)->get(route('admin.vendor-wallets.show', $this->vendor))->assertOk();
        $this->actingAs($manager)->post($url, ['type' => 'credit', 'amount' => 100, 'reason' => 'Goodwill bonus'])->assertForbidden();
        $this->actingAs($this->admin)->post($url, ['type' => 'credit', 'amount' => 100, 'reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(0.0, $this->money()['balance']);

        $this->actingAs($this->admin)->post($url, ['type' => 'credit', 'amount' => 250.50, 'reason' => 'Goodwill bonus'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post($url, ['type' => 'debit', 'amount' => 50, 'reason' => 'Damaged goods recovery'])->assertSessionHasNoErrors();
        $this->assertSame(200.5, $this->money()['balance']);

        $last = VendorWalletTransaction::latest('id')->first();
        $this->assertSame($this->admin->id, $last->created_by);
        $this->assertSame(200.5, (float) $last->balance_after);

        // Adjusted money can be withdrawn like any other.
        config(['shop.withdrawals.minimum' => 100]);
        $this->actingAs($this->owner)->post(route('vendor.withdrawals.store'), ['amount' => 200, 'method' => 'upi'])->assertSessionHasNoErrors();
        $this->assertSame(0.5, $this->money()['balance']);
    }

    public function test_wallet_pages_render_and_the_statement_downloads(): void
    {
        $this->line(1200, 'delivered');
        $this->actingAs($this->admin)->post(route('admin.vendor-wallets.adjust', $this->vendor), ['type' => 'credit', 'amount' => 50, 'reason' => 'Welcome credit']);

        $this->actingAs($this->owner)->get(route('vendor.wallet.index'))->assertOk()->assertSee('Available to withdraw')->assertSee('Welcome credit')->assertSee('1,250.00');
        $this->actingAs($this->owner)->get(route('vendor.wallet.index', ['type' => 'debit']))->assertOk()->assertDontSee('Welcome credit');
        $csv = $this->actingAs($this->owner)->get(route('vendor.wallet.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Welcome credit', $csv);
        $this->assertStringContainsString('Credit', $csv);

        $this->actingAs($this->admin)->get(route('admin.vendor-wallets.index'))->assertOk()->assertSee('Acme Furniture')->assertSee('1,250.00');
        $this->actingAs($this->admin)->get(route('admin.vendor-wallets.show', $this->vendor))->assertOk()->assertSee('Manual adjustment');
        $this->actingAs($this->admin)->get(route('admin.vendor-withdrawals.index'))->assertOk();
    }

    public function test_one_seller_never_sees_another_sellers_wallet(): void
    {
        $this->line(1000, 'delivered');
        $other = User::factory()->create();
        $other->assignRole('vendor');
        Vendor::create(['user_id' => $other->id, 'business_name' => 'Other Co', 'slug' => 'other', 'email' => 'o@e.com', 'status' => 'approved', 'is_active' => true]);

        $this->actingAs($other)->get(route('vendor.wallet.index'))->assertOk()->assertDontSee('Order ORD-W');
        $this->actingAs($other)->get(route('admin.vendor-wallets.index'))->assertForbidden();
    }

    public function test_orders_made_before_the_wallet_are_brought_in_when_it_is_switched_on(): void
    {
        $delivered = $this->line(1000, 'delivered');
        $paid = $this->line(600, 'delivered');
        $paid->update(['payout_status' => 'paid', 'paid_at' => now()]);
        $this->line(300, 'shipped');
        $waiting = VendorWithdrawal::create(['vendor_id' => $this->vendor->id, 'amount' => 250, 'status' => 'pending', 'method' => 'upi', 'vendor_order_ids' => []]);

        // Rewind to the day before the wallet existed, then run the migration the way a live server would.
        Schema::dropIfExists('vendor_wallet_transactions');
        $migration = require base_path('database/migrations/2026_10_23_100000_create_vendor_wallet_transactions.php');
        $migration->up();

        $m = $this->money();
        $this->assertSame(1000.0 + 600.0 - 600.0 - 250.0, $m['balance'], 'delivered earnings, minus what was paid, minus the open request');
        $this->assertSame(750.0, $m['available']);
        $this->assertSame(850.0, $m['withdrawn']);

        // Running it again changes nothing.
        $migration->up();
        $this->assertSame(750.0, $this->money()['available']);
        $this->assertNotNull($delivered->fresh());
        $this->assertNotNull($waiting->fresh());
    }

    public function test_a_server_without_the_wallet_table_still_takes_orders(): void
    {
        // A server one step behind: no wallet table and no delivered_at column yet.
        Schema::dropIfExists('vendor_wallet_transactions');
        Schema::table('vendor_orders', fn ($t) => $t->dropColumn('delivered_at'));
        (new \ReflectionProperty(WalletService::class, 'known'))->setValue(null, false);

        $line = $this->line(500, 'pending');
        $line->update(['status' => 'delivered']);
        $this->actingAs($this->owner)->get(route('vendor.dashboard'))->assertOk();
        $this->actingAs($this->owner)->get(route('vendor.withdrawals.index'))->assertOk();

        $this->assertSame('delivered', $line->fresh()->status);
        $this->assertSame(0, DB::table('vendor_orders')->where('payout_status', 'failed')->count());
    }
}

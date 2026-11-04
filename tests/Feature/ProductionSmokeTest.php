<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCoupon;
use App\Models\VendorOrder;
use App\Models\VendorWithdrawal;
use App\Support\AdminArea;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Read-only smoke and logic tests for Interior Marketplace, run against the development database.
 * Every test runs inside a transaction that is rolled back, so nothing is saved or deleted.
 *
 *   php artisan test tests/Feature/ProductionSmokeTest.php
 *
 * Opens every page of the store, the customer account, the seller panel and the admin panel as the
 * matching demo account and reports any page that crashes (HTTP 500) or is missing (404 where it should exist).
 */
class ProductionSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** Routes that are not pages (downloads, redirects to payment gateways, debug tools). */
    private const SKIP = [
        'logout', 'payments.razorpay.*', 'sanctum.*', 'ignition.*', 'storage.*', 'storage.public', 'livewire.*',
        '*.pdf', '*.export', 'admin.exports.*', 'admin.newsletters.export', 'admin.seo.sitemap*',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('users') || ! User::where('email', 'admin@example.com')->exists()) {
            $this->markTestSkipped('These tests use the development database with its demo accounts. Check DB_* in phpunit.xml (comment out a sqlite / :memory: database there) and run "php artisan db:seed --class=DemoAccountsSeeder" first.');
        }
    }

    public function test_storefront_pages_open(): void
    {
        $this->assertPagesOpen(null, fn (string $name) => ! Str::startsWith($name, ['admin.', 'vendor.', 'customer.']) || in_array($name, ['customer.login', 'customer.register', 'vendor.login', 'vendor.register'], true));

        $failures = [];
        if ($product = Product::where('is_active', true)->where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_status', 'approved'))->first()) {
            $this->check($failures, 'product page', route('products.show', $product));
        }
        if ($vendor = Vendor::where('status', 'approved')->where('is_active', true)->first()) {
            $this->check($failures, 'store page', route('stores.show', $vendor));
        }
        if ($category = Category::where('status', true)->first()) {
            $this->check($failures, 'category page', $category->shopUrl());
        }
        $this->report($failures);
    }

    public function test_customer_pages_open(): void
    {
        $customer = User::where('email', 'demo.customer@example.com')->firstOrFail();
        $this->assertPagesOpen($customer, fn (string $name) => Str::startsWith($name, 'customer.') && ! Str::endsWith($name, ['login', 'register']));

        $failures = [];
        if ($order = Order::where('user_id', $customer->id)->latest('id')->first()) {
            $this->check($failures, 'customer order', route('customer.orders.show', $order), $customer);
        }
        $this->report($failures);
    }

    public function test_vendor_pages_open(): void
    {
        $vendorUser = User::where('email', 'vendor@example.com')->firstOrFail();
        $this->assertPagesOpen($vendorUser, fn (string $name) => Str::startsWith($name, 'vendor.') && ! Str::endsWith($name, ['login', 'register', 'application']));

        $failures = [];
        $vendor = $vendorUser->vendorAccount();
        if ($vendor && ($vo = VendorOrder::where('vendor_id', $vendor->id)->first())) {
            $this->check($failures, 'vendor order', route('vendor.orders.show', $vo), $vendorUser);
        }
        if ($vendor && ($p = $vendor->products()->first())) {
            $this->check($failures, 'vendor product edit', route('vendor.products.edit', $p), $vendorUser);
        }
        $this->report($failures);
    }

    public function test_admin_pages_open(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertPagesOpen($admin, fn (string $name) => Str::startsWith($name, 'admin.') && ! Str::endsWith($name, ['login', 'login.post']));

        $failures = [];
        if ($order = Order::latest('id')->first()) {
            $this->check($failures, 'admin order', route('admin.orders.show', $order), $admin);
        }
        if ($vendor = Vendor::first()) {
            $this->check($failures, 'admin vendor', route('admin.vendors.show', $vendor), $admin);
        }
        if ($product = Product::first()) {
            $this->check($failures, 'admin product edit', route('admin.products.edit', $product), $admin);
        }
        if (class_exists(\App\Models\SupportTicket::class) && ($t = \App\Models\SupportTicket::first())) {
            $this->check($failures, 'admin ticket', route('admin.support-tickets.show', $t), $admin);
        }
        $this->report($failures);
    }

    public function test_staff_roles_only_open_their_area(): void
    {
        $failures = [];
        foreach (AdminArea::ROLES as $role => [, , $patterns]) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user = User::create(['name' => 'Test ' . $role, 'email' => 'smoke-' . $role . '@example.test', 'password' => bcrypt(Str::random(20))]);
            $user->assignRole($role);

            $allowed = collect(Route::getRoutes()->getRoutesByName())->keys()->first(fn ($n) => Str::is($patterns, $n) && Str::endsWith($n, '.index'));
            if ($allowed) {
                $status = $this->actingAs($user)->get(route($allowed))->status();
                if ($status !== 200) {
                    $failures[] = "$role should open $allowed but got $status";
                }
            }
            $status = $this->actingAs($user)->get(route('admin.settings.index'))->status();
            if ($status !== 403) {
                $failures[] = "$role should NOT open admin settings but got $status";
            }
        }
        $this->report($failures);
    }

    public function test_bulk_pricing_tiers(): void
    {
        config(['shop.bulk_pricing.enabled' => true, 'shop.bulk_pricing.default_tiers' => [2 => 5, 5 => 10]]);
        $p = new Product(['price' => 1000]);
        $this->assertSame(1000.0, $p->bulkUnitPrice(1000, 1));
        $this->assertSame(950.0, $p->bulkUnitPrice(1000, 2));
        $this->assertSame(950.0, $p->bulkUnitPrice(1000, 4));
        $this->assertSame(900.0, $p->bulkUnitPrice(1000, 5));

        $p->bulk_pricing = [['min' => 3, 'off' => 7.5], ['min' => 10, 'off' => 12]];
        $this->assertSame(1000.0, $p->bulkUnitPrice(1000, 2));
        $this->assertSame(925.0, $p->bulkUnitPrice(1000, 3));
        $this->assertSame(880.0, $p->bulkUnitPrice(1000, 10));

        $p->bulk_pricing = [];
        $this->assertSame(1000.0, $p->bulkUnitPrice(1000, 50));
    }

    public function test_seller_coupon_only_discounts_that_sellers_items(): void
    {
        if (! Schema::hasTable('vendor_coupons')) {
            $this->markTestSkipped('Run the 2026_10_15 migration first.');
        }
        $vendor = Vendor::where('status', 'approved')->first();
        $own = $vendor ? Product::where('vendor_id', $vendor->id)->first() : null;
        $other = $vendor ? Product::where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_id', '!=', $vendor->id))->first() : null;
        if (! $own || ! $other) {
            $this->markTestSkipped('Needs an approved seller with a product, and one product from someone else.');
        }

        VendorCoupon::create(['vendor_id' => $vendor->id, 'code' => 'SMOKE10', 'type' => 'percent', 'value' => 10, 'min_order' => 0, 'is_active' => true]);
        $lines = [
            ['product_id' => $own->id, 'category_id' => $own->category_id, 'total' => 2000.0],
            ['product_id' => $other->id, 'category_id' => $other->category_id, 'total' => 3000.0],
        ];
        $result = app(\App\Services\CouponService::class)->apply('SMOKE10', $lines);

        $this->assertNull($result['error']);
        $this->assertEquals(200.0, $result['discount']);
        $this->assertEquals(200.0, $result['allocations'][0]);
        $this->assertEquals(0.0, $result['allocations'][1]);
    }

    public function test_withdrawal_never_claims_the_same_order_twice(): void
    {
        if (! Schema::hasTable('vendor_withdrawals')) {
            $this->markTestSkipped('Run the 2026_10_15 migration first.');
        }
        $vendor = Vendor::first();
        $before = VendorWithdrawal::availableLines($vendor)->pluck('id');
        if ($before->isEmpty()) {
            $this->markTestSkipped('The demo seller has no delivered, unpaid orders.');
        }
        $vendor->withdrawals()->create(['amount' => 1, 'status' => 'pending', 'vendor_order_ids' => [$before->first()]]);
        $after = VendorWithdrawal::availableLines($vendor)->pluck('id');

        $this->assertNotContains($before->first(), $after->all());
    }

    public function test_category_cannot_be_inside_itself(): void
    {
        $category = Category::first();
        if (! $category) {
            $this->markTestSkipped('No categories.');
        }
        $this->expectException(ValidationException::class);
        $category->parent_id = $category->id;
        $category->save();
    }

    // ----------------------------------------------------------------------------------------------

    /** GET every named route without parameters that passes $filter, as $user, and fail with a list of broken pages. */
    private function assertPagesOpen(?User $user, callable $filter): void
    {
        $failures = [];
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{') || Str::is(self::SKIP, $name) || ! $filter($name)) {
                continue;
            }
            $checked++;
            $this->check($failures, $name, url($route->uri()), $user);
        }
        fwrite(STDERR, "\n  Checked {$checked} pages" . ($user ? " as {$user->email}" : ' as a visitor') . "\n");
        $this->report($failures);
    }

    private function check(array &$failures, string $label, string $url, ?User $user = null): void
    {
        $this->app['auth']->forgetGuards();
        $response = $user ? $this->actingAs($user)->get($url) : $this->get($url);
        $status = $response->status();
        if ($status >= 500 || $status === 404 || $status === 405) {
            $why = $response->exception ? get_class($response->exception) . ': ' . Str::limit($response->exception->getMessage(), 220) : '';
            $failures[] = "[{$status}] {$label}  {$url}  {$why}";
        }
    }

    private function report(array $failures): void
    {
        $this->assertSame([], $failures, "Broken pages:\n" . implode("\n", $failures));
    }
}

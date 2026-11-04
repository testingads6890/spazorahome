<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function vendorFor(User $u, string $status = 'approved'): Vendor
    {
        return Vendor::create(['user_id' => $u->id, 'business_name' => 'V' . $u->id, 'slug' => 'v' . $u->id, 'email' => "v{$u->id}@e.com", 'status' => $status, 'is_active' => $status === 'approved']);
    }

    public function test_admin_pages_share_the_responsive_shell_with_sidebar_and_top_bar(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('panel-sidebar', false)->assertSee('panelToggle', false)->assertSee('name="viewport"', false)
            ->assertSee('css/panel.css', false)->assertSee('Catalogue')->assertSee('Marketplace')->assertSee('Roles and access');
    }

    public function test_every_sidebar_link_points_at_a_real_admin_route(): void
    {
        foreach (\App\Support\PanelNav::admin() as $items) {
            foreach ($items as [$route]) {
                $this->assertTrue(Route::has($route), "Sidebar link $route does not exist");
            }
        }
        foreach (\App\Support\PanelNav::vendor(null) as $items) {
            foreach ($items as [$route]) {
                $this->assertTrue(Route::has($route), "Sidebar link $route does not exist");
            }
        }
    }

    public function test_active_menu_item_is_highlighted(): void
    {
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->get(route('admin.orders.index'))->getContent();
        $this->assertMatchesRegularExpression('/class="active"[^>]*aria-current="page"[^>]*>\s*<i class="bi bi-cart3"><\/i> Orders/', $html);
    }

    public function test_sidebar_badges_show_real_counts_of_things_needing_attention(): void
    {
        $admin = $this->user('admin');
        $u = $this->user('vendor');
        $vendor = $this->vendorFor($u, 'pending');
        $customer = $this->user('customer');
        Order::create(['user_id' => $customer->id, 'order_number' => 'O1', 'status' => 'pending', 'subtotal' => 1, 'total' => 1, 'currency' => 'INR']);
        SupportTicket::create(['user_id' => $customer->id, 'ticket_number' => 'T1', 'subject' => 's', 'description' => 'd', 'category' => 'other', 'priority' => 'low']);

        $counts = collect(\App\Support\PanelNav::admin())->flatten(1)->mapWithKeys(fn ($i) => [$i[0] => $i[4]]);
        $this->assertSame(1, $counts['admin.vendors.index']);
        $this->assertSame(1, $counts['admin.orders.index']);
        $this->assertSame(1, $counts['admin.support-tickets.index']);
        $this->assertSame(0, $counts['admin.products.index']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('badge-count', false);
        // Approving the vendor clears its badge.
        $vendor->update(['status' => 'approved', 'is_active' => true]);
        $this->assertSame(0, collect(\App\Support\PanelNav::admin())->flatten(1)->mapWithKeys(fn ($i) => [$i[0] => $i[4]])['admin.vendors.index']);
    }

    public function test_vendor_panel_uses_the_same_shell_with_seller_menu_and_only_own_pending_orders(): void
    {
        $u = $this->user('vendor');
        $vendor = $this->vendorFor($u);

        $this->actingAs($u)->get(route('vendor.dashboard'))->assertOk()
            ->assertSee('panel-sidebar', false)->assertSee('Seller')->assertSee('Business profile')->assertSee('Payouts');

        $nav = collect(\App\Support\PanelNav::vendor($vendor))->flatten(1)->mapWithKeys(fn ($i) => [$i[0] => $i[4]]);
        $this->assertSame(0, $nav['vendor.orders.index']);
    }

    public function test_flash_messages_render_in_the_shared_toast_area(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->withSession(['success' => 'Saved it'])->get(route('admin.dashboard'))->assertSee('panel-flash', false)->assertSee('Saved it');
    }

    public function test_panel_is_not_reachable_by_other_roles(): void
    {
        $customer = $this->user('customer');

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_seeded_demo_accounts_can_actually_sign_in_to_their_panels(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\VendorSeeder::class);

        $vendor = User::where('email', 'vendor@example.com')->firstOrFail();
        $this->assertTrue($vendor->hasRole('vendor'), 'the demo vendor needs the vendor role');
        $this->assertTrue(\App\Support\GstState::isValidGstin($vendor->vendor->tax_number));

        $this->post(route('vendor.login.post'), ['email' => 'vendor@example.com', 'password' => 'password'])->assertRedirect();
        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('panel-sidebar', false);
        $this->post(route('logout'));
        $this->post(route('admin.login.post'), ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/admin/dashboard');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_vendor_dashboard_shows_real_sales_payables_and_top_products(): void
    {
        $u = $this->user('vendor');
        $vendor = $this->vendorFor($u);
        $customer = $this->user('customer');
        $cat = \App\Models\Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $p = \App\Models\Product::create(['category_id' => $cat->id, 'vendor_id' => $vendor->id, 'name' => 'Teak Bench', 'slug' => 'teak', 'description' => 'x', 'sku' => 'T1', 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true, 'vendor_status' => 'approved']);

        $make = function (string $status, string $payout) use ($customer, $vendor, $p) {
            $o = Order::create(['user_id' => $customer->id, 'order_number' => 'O-' . uniqid(), 'status' => $status, 'subtotal' => 1000, 'total' => 1000, 'currency' => 'INR']);
            $item = $o->items()->create(['product_id' => $p->id, 'vendor_id' => $vendor->id, 'product_name' => 'Teak Bench', 'product_sku' => 'T1', 'quantity' => 2, 'unit_price' => 500, 'subtotal' => 1000]);

            return \App\Models\VendorOrder::create(['vendor_id' => $vendor->id, 'order_id' => $o->id, 'order_item_id' => $item->id, 'status' => 'pending', 'subtotal' => 1000, 'commission' => 100, 'payout_amount' => 900, 'payout_status' => $payout]);
        };
        $make('processing', 'pending');
        $make('cancelled', 'failed'); // must not count anywhere
        \App\Models\VendorPayout::create(['vendor_id' => $vendor->id, 'amount' => 250, 'method' => 'bank', 'status' => 'completed']);

        $this->actingAs($u)->get(route('vendor.dashboard'))->assertOk()
            ->assertSee('Welcome back, ' . $vendor->business_name)
            ->assertSee('₹1,000.00')   // gross sales, cancelled excluded
            ->assertSee('₹900.00')     // payable
            ->assertSee('₹100.00')     // commission
            ->assertSee('₹250.00')     // already paid out
            ->assertSee('Teak Bench')->assertSee('1 new order')
            ->assertDontSee('₹2,000.00');
    }
}

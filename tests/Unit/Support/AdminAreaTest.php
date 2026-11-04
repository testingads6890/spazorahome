<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\AdminArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAreaTest extends TestCase
{
    use RefreshDatabase;

    private function user(?string $role = null): User
    {
        $user = User::factory()->create();
        if ($role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_panel_roles_include_admin_and_every_staff_role(): void
    {
        $this->assertSame(['admin', 'vendor_manager', 'product_manager', 'order_manager', 'support_manager', 'support_executive'], AdminArea::panelRoles());
    }

    public function test_only_panel_roles_are_staff(): void
    {
        $this->assertFalse(AdminArea::isStaff(null));
        $this->assertFalse(AdminArea::isStaff($this->user('customer')));
        $this->assertFalse(AdminArea::isStaff($this->user('vendor')));
        $this->assertFalse(AdminArea::isStaff($this->user()));
        $this->assertTrue(AdminArea::isStaff($this->user('admin')));
        $this->assertTrue(AdminArea::isStaff($this->user('support_executive')));
    }

    public function test_admin_is_allowed_everywhere(): void
    {
        $admin = $this->user('admin');
        $this->assertTrue(AdminArea::allows($admin, 'admin.settings.index'));
        $this->assertTrue(AdminArea::allows($admin, 'admin.anything.at.all'));
    }

    public function test_guests_customers_and_vendors_are_allowed_nowhere(): void
    {
        $this->assertFalse(AdminArea::allows(null, 'admin.dashboard'));
        $this->assertFalse(AdminArea::allows($this->user('customer'), 'admin.dashboard'));
        $this->assertFalse(AdminArea::allows($this->user('vendor'), 'admin.orders.index'));
    }

    public function test_staff_roles_are_limited_to_their_patterns(): void
    {
        $orders = $this->user('order_manager');
        $this->assertTrue(AdminArea::allows($orders, 'admin.dashboard'));
        $this->assertTrue(AdminArea::allows($orders, 'admin.orders.show'));
        $this->assertTrue(AdminArea::allows($orders, 'admin.reports.sales'));
        $this->assertFalse(AdminArea::allows($orders, 'admin.reports.vendors'));
        $this->assertFalse(AdminArea::allows($orders, 'admin.products.index'));

        $exec = $this->user('support_executive');
        $this->assertTrue(AdminArea::allows($exec, 'admin.support-tickets.index'));
        $this->assertFalse(AdminArea::allows($exec, 'admin.customers.index'));

        $products = $this->user('product_manager');
        $this->assertTrue(AdminArea::allows($products, 'admin.vendor-products.index'));
        $this->assertFalse(AdminArea::allows($products, 'admin.vendors.index'));
    }

    public function test_multiple_staff_roles_combine(): void
    {
        $user = $this->user('support_executive');
        Role::firstOrCreate(['name' => 'product_manager', 'guard_name' => 'web']);
        $user->assignRole('product_manager');

        $this->assertTrue(AdminArea::allows($user, 'admin.support-tickets.index'));
        $this->assertTrue(AdminArea::allows($user, 'admin.products.edit'));
    }

    public function test_home_is_the_first_area_of_the_role(): void
    {
        $this->assertSame(route('admin.dashboard'), AdminArea::home($this->user('admin')));
        $this->assertSame(route('admin.support-tickets.index'), AdminArea::home($this->user('support_executive')));
        $this->assertSame(route('admin.orders.index'), AdminArea::home($this->user('order_manager')));
        $this->assertSame(route('admin.vendors.index'), AdminArea::home($this->user('vendor_manager')));
        $this->assertSame(route('admin.products.index'), AdminArea::home($this->user('product_manager')));
        $this->assertSame(route('home'), AdminArea::home($this->user('customer')));
    }

    public function test_only_admins_and_support_managers_see_all_tickets(): void
    {
        $this->assertTrue(AdminArea::seesAllTickets($this->user('admin')));
        $this->assertTrue(AdminArea::seesAllTickets($this->user('support_manager')));
        $this->assertFalse(AdminArea::seesAllTickets($this->user('support_executive')));
        $this->assertFalse(AdminArea::seesAllTickets(null));
    }
}

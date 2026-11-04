<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Who can open which area, for each kind of visitor: guest, customer, vendor (approved / pending / staff) and admin.
 * 200 = page opens, 403 = refused, 'login' = sent to sign in.
 */
class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function as(string $who): ?User
    {
        if ($who === 'guest') {
            return null;
        }
        $role = match ($who) {
            'vendor', 'pending_vendor', 'vendor_staff' => 'vendor',
            default => $who,
        };
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        if (in_array($who, ['vendor', 'pending_vendor'], true)) {
            $active = $who === 'vendor';
            Vendor::create([
                'user_id' => $user->id, 'business_name' => 'Shop ' . $user->id, 'slug' => 'shop-' . $user->id,
                'email' => "shop{$user->id}@example.com", 'status' => $active ? 'approved' : 'pending', 'is_active' => $active,
            ]);
        }
        if ($who === 'vendor_staff') {
            $owner = User::factory()->create();
            $vendor = Vendor::create([
                'user_id' => $owner->id, 'business_name' => 'Staffed', 'slug' => 'staffed-' . $owner->id,
                'email' => "staffed{$owner->id}@example.com", 'status' => 'approved', 'is_active' => true,
            ]);
            VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => 'staff', 'permissions' => ['orders']]);
        }

        return $user->fresh();
    }

    public static function matrix(): array
    {
        //            who               route                  expected
        return [
            ['guest', 'home', 200],
            ['guest', 'customer.cart.index', 200],
            ['guest', 'customer.dashboard', 'login'],
            ['guest', 'vendor.dashboard', 'login'],
            ['guest', 'admin.dashboard', 'login'],
            ['guest', 'dashboard', 'login'],

            ['customer', 'home', 200],
            ['customer', 'customer.cart.index', 200],
            ['customer', 'customer.dashboard', 200],
            ['customer', 'customer.orders.index', 200],
            ['customer', 'customer.wishlists.index', 200],
            ['customer', 'vendor.dashboard', 403],
            ['customer', 'admin.dashboard', 403],
            ['customer', 'admin.orders.index', 403],

            ['vendor', 'home', 200],
            ['vendor', 'vendor.dashboard', 200],
            ['vendor', 'vendor.orders.index', 200],
            ['vendor', 'vendor.payouts.index', 200],
            ['vendor', 'vendor.profile.edit', 200],
            ['vendor', 'customer.cart.index', 403],
            ['vendor', 'customer.dashboard', 403],
            ['vendor', 'admin.dashboard', 403],

            ['pending_vendor', 'vendor.dashboard', 403],
            ['pending_vendor', 'vendor.orders.index', 403],

            ['vendor_staff', 'vendor.dashboard', 200],
            ['vendor_staff', 'vendor.orders.index', 200],
            ['vendor_staff', 'vendor.payouts.index', 403],
            ['vendor_staff', 'vendor.profile.edit', 403],

            ['admin', 'home', 200],
            ['admin', 'admin.dashboard', 200],
            ['admin', 'admin.orders.index', 200],
            ['admin', 'admin.vendors.index', 200],
            ['admin', 'admin.products.index', 200],
            ['admin', 'customer.cart.index', 403],
            ['admin', 'customer.dashboard', 403],
            ['admin', 'vendor.dashboard', 403],
        ];
    }

    #[DataProvider('matrix')]
    public function test_access(string $who, string $route, int|string $expected): void
    {
        $user = $this->as($who);
        $response = ($user ? $this->actingAs($user) : $this)->get(route($route));

        if ($expected === 'login') {
            $this->assertGuest();
            $this->assertTrue($response->isRedirect(), "$who → $route should redirect to login, got {$response->status()}");
            $this->assertStringContainsString('login', (string) $response->headers->get('Location'));
        } else {
            $response->assertStatus($expected);
        }
    }

    public function test_dashboard_link_sends_each_role_to_its_own_panel(): void
    {
        $this->actingAs($this->as('admin'))->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->as('vendor'))->get(route('dashboard'))->assertRedirect(route('vendor.dashboard'));
        $this->actingAs($this->as('customer'))->get(route('dashboard'))->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_staff_role_is_redirected_from_dashboard_to_its_area_and_kept_out_of_others(): void
    {
        $support = $this->as('support_executive');

        $this->actingAs($support)->get(route('admin.dashboard'))->assertRedirect(route('admin.support-tickets.index'));
        $this->actingAs($support)->get(route('admin.support-tickets.index'))->assertOk();
        $this->actingAs($support)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_signed_in_users_cannot_open_login_or_guest_checkout_pages(): void
    {
        foreach (['customer', 'vendor', 'admin'] as $who) {
            $this->actingAs($this->as($who))->get(route('login'))->assertRedirect();
        }
    }
}

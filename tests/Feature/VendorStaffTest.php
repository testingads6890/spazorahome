<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Seller staff: their own sign-in, limited to the areas the shop owner allows. */
class VendorStaffTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['vendor', 'customer', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->owner = User::factory()->create();
        $this->owner->assignRole('vendor');
        $this->vendor = Vendor::create(['user_id' => $this->owner->id, 'business_name' => 'Acme Furniture', 'slug' => 'acme', 'email' => 'a@e.com', 'status' => 'approved', 'is_active' => true]);
    }

    private function addStaff(array $overrides = []): User
    {
        $this->actingAs($this->owner)->post(route('vendor.staff.store'), array_merge([
            'name' => 'Ravi', 'email' => 'ravi@example.com', 'role' => 'staff', 'permissions' => ['products'],
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ], $overrides))->assertRedirect(route('vendor.staff.index'));
        auth()->logout();

        return User::whereRaw('lower(email) = ?', [mb_strtolower($overrides['email'] ?? 'ravi@example.com')])->first();
    }

    public function test_owner_creates_a_staff_login_that_can_sign_in_to_the_seller_panel(): void
    {
        $this->actingAs($this->owner)->get(route('vendor.staff.create'))->assertOk()->assertSee('Repeat password');
        $staff = $this->addStaff();

        $this->assertTrue(Hash::check('secret-pass-1', $staff->password));
        $this->assertTrue($staff->hasRole('vendor'));
        $this->assertTrue($staff->vendorAccount()->is($this->vendor));
        $this->assertFalse($staff->isVendorOwner());

        $this->post(route('vendor.login.post'), ['email' => 'ravi@example.com', 'password' => 'secret-pass-1'])->assertRedirect('/vendor/dashboard');
        $this->assertAuthenticatedAs($staff);
        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('Acme Furniture');
        $this->get('/dashboard')->assertRedirect(route('vendor.dashboard'));
    }

    public function test_staff_only_reach_the_areas_ticked_for_them(): void
    {
        $staff = $this->addStaff(['permissions' => ['products']]);
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = Product::create(['category_id' => $category->id, 'vendor_id' => $this->vendor->id, 'vendor_status' => 'approved', 'name' => 'Staff Sofa', 'slug' => 'staff-sofa', 'description' => 'x', 'sku' => 'SS1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true]);

        // Allowed: the shop's products (the owner's, not just ones this person created).
        $this->actingAs($staff)->get(route('vendor.products.index'))->assertOk()->assertSee('Staff Sofa');
        $this->actingAs($staff)->get(route('vendor.products.edit', $product))->assertOk();
        $this->actingAs($staff)->put(route('vendor.products.update', $product), ['name' => 'Renamed', 'description' => 'x', 'price' => 12, 'stock_quantity' => 2])->assertRedirect(route('vendor.products.index'));
        $this->assertSame('Renamed', $product->fresh()->name);

        // Not allowed: orders, payouts, and the owner-only pages.
        foreach (['vendor.orders.index', 'vendor.payouts.index', 'vendor.staff.index', 'vendor.staff.create', 'vendor.profile.edit'] as $name) {
            $this->actingAs($staff)->get(route($name))->assertForbidden();
        }
        $this->actingAs($staff)->put(route('vendor.profile.update'), ['business_name' => 'Hijacked', 'state' => 'Maharashtra'])->assertForbidden();
        $this->actingAs($staff)->post(route('vendor.staff.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'manager', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])->assertForbidden();
        $this->assertSame('Acme Furniture', $this->vendor->fresh()->business_name);

        // The menu and dashboard only offer what they can open, and hide the money figures.
        $this->actingAs($staff)->get(route('vendor.dashboard'))->assertOk()
            ->assertSee(route('vendor.products.index'), false)
            ->assertDontSee(route('vendor.orders.index'), false)->assertDontSee(route('vendor.payouts.index'), false)
            ->assertDontSee(route('vendor.staff.index'), false)->assertDontSee('Payable to you')->assertDontSee('Commission charged');
        $this->actingAs($this->owner)->get(route('vendor.dashboard'))->assertOk()
            ->assertSee(route('vendor.staff.index'), false)->assertSee('Payable to you');
    }

    public function test_owner_can_change_access_and_managers_get_every_selling_area(): void
    {
        $staff = $this->addStaff(['permissions' => []]);
        $member = VendorUser::first();
        $this->actingAs($staff)->get(route('vendor.products.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('vendor.staff.index'))->assertOk()->assertSee('Dashboard only');

        $this->actingAs($this->owner)->get(route('vendor.staff.edit', $member))->assertOk()->assertSee('ravi@example.com');
        $this->actingAs($this->owner)->put(route('vendor.staff.update', $member), ['role' => 'staff', 'permissions' => ['orders', 'payouts']])->assertRedirect(route('vendor.staff.index'));
        $staff = $staff->fresh();
        $this->actingAs($staff)->get(route('vendor.orders.index'))->assertOk();
        $this->actingAs($staff)->get(route('vendor.payouts.index'))->assertOk();
        $this->actingAs($staff)->get(route('vendor.products.index'))->assertForbidden();

        $this->actingAs($this->owner)->put(route('vendor.staff.update', $member), ['role' => 'manager'])->assertRedirect(route('vendor.staff.index'));
        $staff = $staff->fresh();
        foreach (['vendor.products.index', 'vendor.orders.index', 'vendor.payouts.index'] as $name) {
            $this->actingAs($staff)->get(route($name))->assertOk();
        }
        $this->actingAs($staff)->get(route('vendor.staff.index'))->assertForbidden(); // still owner-only

        $this->actingAs($this->owner)->put(route('vendor.staff.update', $member), ['role' => 'owner'])->assertSessionHasErrors('role');
        $this->actingAs($this->owner)->put(route('vendor.staff.update', $member), ['role' => 'staff', 'permissions' => ['owner']])->assertSessionHasErrors('permissions.0');
    }

    public function test_removed_staff_lose_the_seller_panel_but_keep_a_usable_account(): void
    {
        $staff = $this->addStaff();
        $member = VendorUser::first();

        $other = User::factory()->create();
        $other->assignRole('vendor');
        Vendor::create(['user_id' => $other->id, 'business_name' => 'Other', 'slug' => 'other', 'email' => 'o@e.com', 'status' => 'approved', 'is_active' => true]);
        $this->actingAs($other)->delete(route('vendor.staff.destroy', $member))->assertForbidden();
        $this->actingAs($other)->get(route('vendor.staff.edit', $member))->assertForbidden();
        $this->actingAs($other)->put(route('vendor.staff.update', $member), ['role' => 'manager'])->assertForbidden();

        $this->actingAs($this->owner)->delete(route('vendor.staff.destroy', $member))->assertSessionHas('success');
        $staff = $staff->fresh();
        $this->assertSame(0, VendorUser::count());
        $this->assertFalse($staff->hasRole('vendor'));
        $this->assertTrue($staff->hasRole('customer'));
        $this->actingAs($staff)->get(route('vendor.dashboard'))->assertForbidden();
        $this->actingAs($staff)->get(route('customer.dashboard'))->assertOk();
    }

    public function test_existing_accounts_are_linked_without_touching_their_password(): void
    {
        $customer = User::factory()->create(['email' => 'shopper@example.com', 'password' => Hash::make('their-own-password')]);
        $customer->assignRole('customer');

        // The password typed by the owner is ignored for an account that already exists.
        $this->addStaff(['email' => 'Shopper@Example.com', 'password' => 'owner-typed-this', 'password_confirmation' => 'owner-typed-this', 'permissions' => ['orders']]);
        $customer = $customer->fresh();
        $this->assertTrue(Hash::check('their-own-password', $customer->password));
        $this->assertFalse(Hash::check('owner-typed-this', $customer->password));
        $this->assertSame(1, User::whereRaw('lower(email) = ?', ['shopper@example.com'])->count());
        $this->assertTrue($customer->vendorCan('orders'));
        $this->assertTrue($customer->hasRole('customer')); // still a shopper too

        // Removing them leaves the customer account exactly as it was.
        $this->actingAs($this->owner)->delete(route('vendor.staff.destroy', VendorUser::first()));
        $this->assertFalse($customer->fresh()->hasRole('vendor'));
        $this->assertTrue($customer->fresh()->hasRole('customer'));
    }

    public function test_staff_cannot_be_the_owner_another_seller_or_an_admin_and_new_accounts_need_a_password(): void
    {
        $post = fn (array $data) => $this->actingAs($this->owner)->post(route('vendor.staff.store'), array_merge(['name' => 'N', 'role' => 'staff', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'], $data));

        $post(['email' => $this->owner->email])->assertSessionHasErrors('email');

        $seller = User::factory()->create();
        $seller->assignRole('vendor');
        Vendor::create(['user_id' => $seller->id, 'business_name' => 'B', 'slug' => 'b', 'email' => 'b@e.com', 'status' => 'approved', 'is_active' => true]);
        $post(['email' => $seller->email])->assertSessionHasErrors('email');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $post(['email' => $admin->email])->assertSessionHasErrors('email');

        $post(['email' => 'new@example.com', 'password' => '', 'password_confirmation' => ''])->assertSessionHasErrors('password');
        $post(['email' => 'new@example.com', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $post(['email' => 'new@example.com', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->assertSame(0, VendorUser::count());
        $this->assertNull(User::where('email', 'new@example.com')->first());

        // Someone already working for this shop cannot be added twice, here or to another shop.
        $this->addStaff(['email' => 'new@example.com']);
        $post(['email' => 'new@example.com'])->assertSessionHasErrors('email');
        $this->actingAs($seller)->post(route('vendor.staff.store'), ['name' => 'N', 'email' => 'new@example.com', 'role' => 'staff'])->assertSessionHasErrors('email');
        $this->assertSame(1, VendorUser::count());
    }

    public function test_staff_of_a_shop_that_is_not_approved_see_the_pending_page(): void
    {
        $staff = $this->addStaff();
        $this->vendor->update(['is_active' => false]);

        $this->actingAs($staff->fresh())->get(route('vendor.dashboard'))->assertForbidden()->assertSee('Acme Furniture');
    }
}

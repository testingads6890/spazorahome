<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function vendorFor(User $user, bool $active = true): Vendor
    {
        return Vendor::create([
            'user_id' => $user->id, 'business_name' => 'V' . $user->id, 'slug' => 'v' . $user->id,
            'email' => "v{$user->id}@example.com", 'status' => $active ? 'approved' : 'pending', 'is_active' => $active,
        ]);
    }

    private function order(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-' . $user->id, 'status' => 'pending',
            'subtotal' => 10, 'total' => 10, 'currency' => 'INR',
        ]);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        [$a, $b] = [$this->userWithRole('customer'), $this->userWithRole('customer')];
        $order = $this->order($a);

        $this->actingAs($a)->get(route('customer.orders.show', $order))->assertOk();
        $this->actingAs($b)->get(route('customer.orders.show', $order))->assertForbidden();
    }

    public function test_customer_cannot_edit_or_delete_another_customers_address(): void
    {
        [$a, $b] = [$this->userWithRole('customer'), $this->userWithRole('customer')];
        $address = $a->addresses()->create([
            'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 St',
            'city' => 'Pune', 'state' => 'MH', 'postal_code' => '411001', 'country' => 'IN',
        ]);

        $this->actingAs($a)->get(route('customer.addresses.edit', $address))->assertOk();
        $this->actingAs($b)->get(route('customer.addresses.edit', $address))->assertForbidden();
        $this->actingAs($b)->delete(route('customer.addresses.destroy', $address))->assertForbidden();
        $this->assertNotNull($address->fresh());
    }

    public function test_customer_can_add_an_address_from_the_form(): void
    {
        $user = $this->userWithRole('customer');

        $this->actingAs($user)->get(route('customer.addresses.create'))->assertOk();
        $this->actingAs($user)->post(route('customer.addresses.store'), [
            'type' => 'shipping', 'first_name' => 'Asha', 'last_name' => 'K', 'address_line_1' => '1 Road',
            'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India', 'is_default' => '1',
        ])->assertRedirect(route('customer.addresses.index'));

        $this->assertSame('Asha K', $user->addresses()->first()->full_name);
        $this->actingAs($user)->get(route('customer.addresses.index'))->assertOk();
    }

    public function test_roles_cannot_cross_panels(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('customer.orders.index'))->assertRedirect(route('login'));
        $this->get(route('customer.checkout.index'))->assertRedirect(route('login'));
        // The cart itself is open to visitors since guest checkout (see GuestCheckoutTest).
        $this->get(route('customer.cart.index'))->assertOk();
    }

    public function test_unapproved_vendor_is_blocked_from_vendor_panel(): void
    {
        $user = $this->userWithRole('vendor');
        $this->vendorFor($user, active: false);

        $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_vendor_cannot_open_another_vendors_order(): void
    {
        [$u1, $u2] = [$this->userWithRole('vendor'), $this->userWithRole('vendor')];
        [$v1, $v2] = [$this->vendorFor($u1), $this->vendorFor($u2)];

        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true, 'vendor_id' => $v1->id,
        ]);
        $order = $this->order($this->userWithRole('customer'));
        $item = $order->items()->create([
            'product_id' => $product->id, 'vendor_id' => $v1->id, 'product_name' => 'Sofa', 'product_sku' => 'S1',
            'quantity' => 1, 'unit_price' => 10, 'subtotal' => 10,
        ]);
        $vo = VendorOrder::create([
            'vendor_id' => $v1->id, 'order_id' => $order->id, 'order_item_id' => $item->id,
            'subtotal' => 10, 'commission' => 1, 'payout_amount' => 9, 'payout_status' => 'pending',
        ]);

        $this->actingAs($u1)->get(route('vendor.orders.show', $vo))->assertOk();
        $this->actingAs($u2)->get(route('vendor.orders.show', $vo))->assertForbidden();
    }

    public function test_admin_detail_pages_render_with_data(): void
    {
        $admin = $this->userWithRole('admin');
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);
        $order = $this->order($this->userWithRole('customer'));

        $failures = [];
        foreach ([
            route('admin.products.index'), route('admin.products.show', $product), route('admin.products.edit', $product),
            route('admin.categories.show', $category), route('admin.categories.edit', $category),
            route('admin.orders.index'), route('admin.orders.show', $order), route('admin.customers.index'),
            route('admin.customers.show', $order->user_id), route('search'), route('products.show', $product),
        ] as $url) {
            try {
                $status = $this->withoutExceptionHandling()->actingAs($admin)->get($url)->getStatusCode();
                if ($status >= 400) {
                    $failures[] = "$status $url";
                }
            } catch (\Throwable $e) {
                $failures[] = "$url: " . strtok($e->getMessage(), "\n");
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_admin_dashboard_shows_real_totals_not_placeholders(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = $this->userWithRole('customer');
        $this->order($customer);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('₹10.00')
            ->assertDontSee('45,678')
            ->assertSee('ORD-' . $customer->id);
    }

    public function test_address_state_must_come_from_the_gst_state_list(): void
    {
        $user = $this->userWithRole('customer');
        $base = [
            'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune',
            'postal_code' => '411001', 'country' => 'India',
        ];

        $this->actingAs($user)->get(route('customer.addresses.create'))->assertOk()
            ->assertSee('Maharashtra (27)', false)->assertSee('Karnataka (29)', false);
        $this->actingAs($user)->post(route('customer.addresses.store'), $base + ['state' => 'Mumbai'])->assertSessionHasErrors('state');
        $this->actingAs($user)->post(route('customer.addresses.store'), $base + ['state' => 'MH'])->assertSessionHasErrors('state');
        $this->actingAs($user)->post(route('customer.addresses.store'), $base + ['state' => 'Karnataka'])->assertSessionHasNoErrors();
        $this->assertSame('29', $user->addresses()->first()->state_code);
    }

    public function test_gst_state_lookup_accepts_name_abbreviation_or_code(): void
    {
        foreach (['Maharashtra', ' maharashtra ', 'MH', 'mh', '27'] as $v) {
            $this->assertSame('27', \App\Support\GstState::code($v), $v);
        }
        $this->assertSame('Karnataka', \App\Support\GstState::name('29'));
        $this->assertNull(\App\Support\GstState::code('Atlantis'));
        $this->assertNull(\App\Support\GstState::code(''));
        $this->assertCount(36, \App\Support\GstState::options());
    }
}

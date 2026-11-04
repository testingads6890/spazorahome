<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $o = []): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $u = User::factory()->create($o);
        $u->assignRole($role);

        return $u;
    }

    private function order(User $u, string $status = 'pending'): Order
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x', 'sku' => 'S-' . uniqid(), 'price' => 500, 'stock_quantity' => 5, 'is_active' => true]);
        $addr = Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India', 'phone' => '999']);
        $o = Order::create(['user_id' => $u->id, 'order_number' => 'ORD-' . uniqid(), 'status' => $status, 'subtotal' => 500, 'tax' => 90, 'shipping' => 0, 'total' => 590, 'currency' => 'INR', 'shipping_address_id' => $addr->id, 'billing_address_id' => $addr->id]);
        $o->items()->create(['product_id' => $p->id, 'product_name' => 'Oak Sofa', 'product_sku' => $p->sku, 'quantity' => 1, 'unit_price' => 500, 'subtotal' => 500]);

        return $o;
    }

    public function test_new_customer_registration_assigns_the_customer_role_and_can_use_the_account(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
        $this->post(route('register.post'), ['name' => 'Asha K', 'email' => 'asha@example.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1', 'phone' => '9876543210'])
            ->assertRedirect();

        $user = User::where('email', 'asha@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertSame('9876543210', $user->profile()->first()->phone);
        $this->assertAuthenticatedAs($user);
        // Before the fix a brand-new account got 403 on every customer page.
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('Hello, Asha');
        $this->get(route('customer.cart.index'))->assertOk();
    }

    public function test_customer_register_page_requires_a_phone_but_the_generic_one_does_not(): void
    {
        $base = ['name' => 'X', 'email' => 'x@example.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1'];

        $this->post(route('customer.register.post'), $base)->assertSessionHasErrors('phone');
        $this->post(route('register.post'), $base)->assertSessionHasNoErrors();
    }

    public function test_registration_validates_email_uniqueness_and_password(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register.post'), ['name' => 'X', 'email' => 'taken@example.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1'])->assertSessionHasErrors('email');
        $this->post(route('register.post'), ['name' => 'X', 'email' => 'new@example.com', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post(route('register.post'), ['name' => 'X', 'email' => 'new@example.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
    }

    public function test_vendor_application_creates_a_pending_vendor_that_cannot_sign_in_to_the_panel_yet(): void
    {
        $this->get(route('vendor.register'))->assertOk()->assertSee('Apply to sell');
        \Illuminate\Support\Facades\Storage::fake('local');
        $category = \App\Models\Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        // The full seller application: business, address, registration, one required document, bank and terms.
        $this->post(route('vendor.register.post'), [
            'name' => 'Ravi', 'email' => 'ravi@shop.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1',
            'company_name' => 'Ravi Woods', 'phone' => '9999999999', 'business_type' => 'manufacturer', 'categories' => [$category->id],
            'description' => 'Solid wood furniture made in our Jaipur workshop since 2005.',
            'address' => '5 Market Rd', 'country' => 'India', 'state' => 'Rajasthan', 'city' => 'Jaipur', 'postal_code' => '302001',
            'registration_number' => 'UDYAM-RJ-17-0000001', 'gst_registered' => 0,
            'doc_registration' => \Illuminate\Http\UploadedFile::fake()->create('registration.pdf', 120, 'application/pdf'),
            'doc_aadhaar' => \Illuminate\Http\UploadedFile::fake()->create('aadhaar.pdf', 90, 'application/pdf'),
            'seller_type' => 'manufacturer',
            'bank_holder' => 'Ravi Woods', 'bank_name' => 'HDFC Bank', 'bank_account' => '50100012345678', 'bank_account_confirmation' => '50100012345678', 'bank_ifsc' => 'HDFC0001234',
            'terms' => 1,
        ])->assertRedirect(route('vendor.login'));

        $vendor = Vendor::where('email', 'ravi@shop.com')->firstOrFail();
        $this->assertSame('pending', $vendor->status);
        $this->assertFalse($vendor->is_active);
        $this->assertSame('ravi-woods', $vendor->slug);
        $this->assertTrue($vendor->user->hasRole('vendor'));
        $this->assertGuest();

        $this->post(route('vendor.login.post'), ['email' => 'ravi@shop.com', 'password' => 'Str0ng!Pass1'])->assertRedirect();
        $this->get(route('vendor.dashboard'))->assertForbidden()->assertSee('Application under review')->assertSee('Ravi Woods');

        $vendor->update(['status' => 'approved', 'is_active' => true]);
        $this->actingAs($vendor->user()->first())->get(route('vendor.dashboard'))->assertOk(); // fresh user: no stale cached relation
    }

    public function test_vendor_application_needs_terms_and_a_valid_state(): void
    {
        $base = ['name' => 'R', 'email' => 'r@shop.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1', 'company_name' => 'R Co', 'phone' => '1', 'address' => 'x', 'city' => 'y'];

        $this->post(route('vendor.register.post'), $base + ['state' => 'Rajasthan'])->assertSessionHasErrors('terms');
        $this->post(route('vendor.register.post'), $base + ['state' => 'Atlantis', 'terms' => 1])->assertSessionHasErrors('state');
        $this->assertSame(0, Vendor::count());
    }

    public function test_login_by_role_lands_on_the_right_dashboard_and_rejects_the_wrong_role(): void
    {
        $customer = $this->user('customer', ['email' => 'c@example.com']);
        $this->user('admin', ['email' => 'a@example.com']);

        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
        $this->get(route('vendor.login'))->assertOk()->assertSee('Seller sign in');

        // A customer cannot use the seller or admin sign-in.
        $this->post(route('vendor.login.post'), ['email' => 'c@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('admin.login.post'), ['email' => 'c@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.post'), ['email' => 'c@example.com', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($customer);
        $this->post(route('logout'));

        // Admin signs in with an email (the old form sent a "username" that maps to no column).
        $this->post(route('admin.login.post'), ['username' => 'a@example.com', 'password' => 'password'])->assertRedirect('/admin/dashboard');
    }

    public function test_wrong_password_shows_one_generic_error_and_login_is_throttled(): void
    {
        $this->user('customer', ['email' => 'c@example.com']);

        $this->post(route('login.post'), ['email' => 'c@example.com', 'password' => 'nope'])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
        $this->post(route('login.post'), ['email' => 'nobody@example.com', 'password' => 'nope'])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

        foreach (range(1, 12) as $i) {
            $last = $this->post(route('login.post'), ['email' => 'c@example.com', 'password' => 'nope']);
        }
        $last->assertStatus(429);
    }

    public function test_dashboard_shows_real_stats_recent_orders_and_ignores_cancelled_spend(): void
    {
        $u = $this->user('customer');
        $this->order($u, 'delivered');
        $this->order($u, 'cancelled');

        $this->actingAs($u)->get(route('customer.dashboard'))->assertOk()
            ->assertSee('Recent orders')->assertSee('₹590.00')->assertSee('Total spent')
            ->assertDontSee('₹1,180.00'); // 590 + 590 would mean the cancelled order was counted
    }

    public function test_orders_list_filters_by_status_and_hides_other_customers_orders(): void
    {
        $u = $this->user('customer');
        $mine = $this->order($u, 'shipped');
        $done = $this->order($u, 'delivered');
        $theirs = $this->order($this->user('customer'), 'shipped');

        $this->actingAs($u)->get(route('customer.orders.index'))->assertOk()->assertSee($mine->order_number)->assertSee($done->order_number)->assertDontSee($theirs->order_number);
        $this->actingAs($u)->get(route('customer.orders.index', ['status' => 'delivered']))->assertOk()->assertSee($done->order_number)->assertDontSee($mine->order_number);
        $this->actingAs($u)->get(route('customer.orders.index', ['status' => 'bogus']))->assertSessionHasErrors('status');
    }

    public function test_order_detail_shows_timeline_items_totals_and_actions(): void
    {
        $u = $this->user('customer');
        $o = $this->order($u, 'processing');

        $this->actingAs($u)->get(route('customer.orders.show', $o))->assertOk()
            ->assertSee('Order progress')->assertSee('Oak Sofa')->assertSee('₹590.00')->assertSee('Cancel order')
            ->assertSee('Delivery address')->assertDontSee(route('customer.orders.invoice', $o), false); // no invoice before it ships

        $o->update(['status' => 'shipped', 'tracking_number' => 'BD123', 'carrier' => 'BlueDart']);
        $this->actingAs($u)->get(route('customer.orders.show', $o->fresh()))->assertOk()
            ->assertSee('BD123')->assertDontSee('Cancel order')->assertSee('Invoice');
    }

    public function test_tracking_page_finds_only_own_orders_and_the_lookup_form_matches_the_controller(): void
    {
        $u = $this->user('customer');
        $o = $this->order($u, 'shipped');
        $o->update(['tracking_number' => 'BD999', 'carrier' => 'BlueDart']);
        $other = $this->order($this->user('customer'), 'shipped');
        $other->update(['tracking_number' => 'ZZ111']);

        $this->actingAs($u)->get(route('customer.order-tracking.index'))->assertOk()->assertSee('name="tracking_number"', false)->assertSee('BD999')->assertDontSee('ZZ111');
        $this->actingAs($u)->get(route('customer.order-tracking.track', ['tracking_number' => ' BD999 ']))->assertOk()->assertSee('Shipment progress')->assertSee('BlueDart');
        $this->actingAs($u)->get(route('customer.order-tracking.track', ['tracking_number' => 'ZZ111']))->assertNotFound();
    }
}

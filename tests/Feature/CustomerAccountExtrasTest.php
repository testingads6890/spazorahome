<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Customer account: invoices list, return requests (request → admin approves → credit note) and "Become a seller". */
class CustomerAccountExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function deliveredOrder(User $user): Order
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);
        $sofa = Product::create(['category_id' => $category->id, 'name' => 'Oak Sofa', 'slug' => 'oak-sofa', 'description' => 'x', 'sku' => 'OS1', 'price' => 1000, 'tax_rate' => 18, 'stock_quantity' => 10, 'is_active' => true]);
        $address = Address::create(['user_id' => $user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Street', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'IN', 'phone' => '9999999999']);
        $this->actingAs($user)->post(route('customer.cart.store', $sofa), ['quantity' => 2]);
        $this->actingAs($user)->post(route('customer.checkout.store'), ['shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod']);
        $order = Order::latest('id')->firstOrFail();
        $admin = $this->user('admin');
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'delivered']);

        return $order->fresh();
    }

    public function test_invoices_page_lists_the_customers_own_invoices_only(): void
    {
        $customer = $this->user('customer');
        $order = $this->deliveredOrder($customer);
        $this->assertNotNull($order->invoice_number);

        $this->actingAs($customer)->get(route('customer.invoices.index'))->assertOk()->assertSee($order->invoice_number)->assertSee(route('customer.orders.invoice', $order), false);
        $this->actingAs($this->user('customer'))->get(route('customer.invoices.index'))->assertOk()->assertDontSee($order->invoice_number)->assertSee('No invoices yet');
    }

    public function test_customer_requests_a_return_and_admin_approval_issues_the_credit_note(): void
    {
        $customer = $this->user('customer');
        $order = $this->deliveredOrder($customer);
        $item = $order->items()->first();

        $this->actingAs($customer)->get(route('customer.orders.show', $order))->assertOk()->assertSee(route('customer.returns.create', $order), false);
        $this->actingAs($customer)->get(route('customer.returns.create', $order))->assertOk()->assertSee('Oak Sofa');
        $this->actingAs($customer)->post(route('customer.returns.store', $order), ['quantities' => [$item->id => 5], 'reason' => 'damaged'])->assertSessionHasErrors('quantities');
        $this->actingAs($customer)->post(route('customer.returns.store', $order), ['quantities' => [$item->id => 1]])->assertSessionHasErrors('reason');
        $this->actingAs($customer)->post(route('customer.returns.store', $order), ['quantities' => [$item->id => 1], 'reason' => 'damaged', 'customer_note' => 'Leg cracked'])
            ->assertRedirect(route('customer.returns.index'));
        $request = ReturnRequest::firstOrFail();
        $this->assertSame([$item->id => 1], $request->items);

        // Someone else cannot open or return this order; a second request waits for the first.
        $this->actingAs($this->user('customer'))->get(route('customer.returns.create', $order))->assertForbidden();
        $this->actingAs($customer)->get(route('customer.returns.create', $order))->assertRedirect(route('customer.returns.index'));

        $admin = User::role('admin')->first();
        $this->actingAs($admin)->get(route('admin.return-requests.index'))->assertOk()->assertSee('Leg cracked')->assertSee('Arrived damaged');
        $this->actingAs($admin)->post(route('admin.return-requests.approve', $request), ['restock' => 1])->assertSessionHas('success');

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->credit_notes);
        $this->assertSame(1, CreditNote::where('order_id', $order->id)->count());
        $this->actingAs($customer)->get(route('customer.returns.index'))->assertOk()->assertSee('Approved')->assertSee($request->credit_notes);
        $this->actingAs($admin)->post(route('admin.return-requests.approve', $request))->assertStatus(422);
    }

    public function test_admin_can_decline_with_a_note_and_the_window_closes_after_the_return_period(): void
    {
        $customer = $this->user('customer');
        $order = $this->deliveredOrder($customer);
        $item = $order->items()->first();
        $this->actingAs($customer)->post(route('customer.returns.store', $order), ['quantities' => [$item->id => 1], 'reason' => 'changed_mind']);

        $admin = User::role('admin')->first();
        $this->actingAs($admin)->post(route('admin.return-requests.reject', ReturnRequest::first()), [])->assertSessionHasErrors('admin_note');
        $this->actingAs($admin)->post(route('admin.return-requests.reject', ReturnRequest::first()), ['admin_note' => 'Used item'])->assertSessionHas('success');
        $this->actingAs($customer)->get(route('customer.returns.index'))->assertSee('Declined')->assertSee('Used item');

        $order->update(['delivered_at' => now()->subDays(30)]);
        $this->actingAs($customer)->get(route('customer.returns.create', $order))->assertRedirect(route('customer.orders.show', $order));
    }

    public function test_a_customer_can_become_a_seller_with_the_same_account(): void
    {
        Storage::fake('local');
        Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $customer = $this->user('customer');
        $category = Category::create(['name' => 'Decor', 'slug' => 'decor']);

        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk()->assertSee(route('customer.become-seller'), false);
        $this->actingAs($customer)->get(route('customer.become-seller'))->assertOk()->assertSee($customer->email)->assertDontSee('name="password"', false);
        $this->actingAs($customer)->post(route('customer.become-seller.store'), [
            'company_name' => 'Brass House', 'phone' => '9876543210', 'business_type' => 'workshop', 'categories' => [$category->id],
            'description' => 'Hand-made brass lamps and decor from Moradabad artisans.',
            'address' => '2 Brass Lane', 'country' => 'India', 'state' => 'Uttar Pradesh', 'city' => 'Moradabad', 'postal_code' => '244001',
            'registration_number' => 'UDYAM-UP-00-0000002', 'gst_registered' => 0,
            'doc_registration' => UploadedFile::fake()->create('reg.pdf', 50, 'application/pdf'),
            'doc_aadhaar' => UploadedFile::fake()->create('aadhaar.jpg', 40, 'image/jpeg'),
            'seller_type' => 'interior_designer',
            'bank_holder' => 'Brass House', 'bank_name' => 'SBI', 'bank_account' => '12345678901', 'bank_account_confirmation' => '12345678901', 'bank_ifsc' => 'SBIN0001234',
            'terms' => 1,
        ])->assertRedirect(route('vendor.application'));

        $customer->refresh();
        $vendor = Vendor::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame('pending', $vendor->status);
        $this->assertSame($customer->email, $vendor->email);
        $this->assertTrue($customer->hasRole('vendor') && $customer->hasRole('customer'));
        $this->assertSame(1, User::where('email', $customer->email)->count());
        // Already a seller: the link is gone and applying again is refused.
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertDontSee(route('customer.become-seller'), false);
        $this->actingAs($customer)->post(route('customer.become-seller.store'), [])->assertForbidden();
    }
}

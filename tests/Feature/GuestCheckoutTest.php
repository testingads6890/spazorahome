<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Guest cart, checkout without registering, and choosing or recovering a password afterwards. */
class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['customer', 'vendor', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x',
            'sku' => 'S-' . uniqid(), 'price' => 1000, 'tax_rate' => 18, 'stock_quantity' => 5, 'is_active' => true,
        ], $overrides));
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Asha', 'last_name' => 'Mehta', 'email' => 'asha@example.com', 'phone' => '9999999999',
            'address_line_1' => '12 MG Road', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001',
        ], $overrides);
    }

    private function customer(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('customer');

        return $user;
    }

    public function test_a_visitor_can_fill_change_and_empty_a_cart_without_signing_in(): void
    {
        $product = $this->product(['stock_quantity' => 3]);

        $this->post(route('customer.cart.store', $product), ['quantity' => 2])->assertSessionHas('success');
        $cart = Cart::first();
        $this->assertNull($cart->user_id);
        $this->assertSame(session(Cart::GUEST_KEY), $cart->session_id);

        $this->get(route('customer.cart.index'))->assertOk()->assertSee('Oak Sofa')->assertSee(route('checkout.guest'), false);
        $this->get(route('home'))->assertOk()->assertSee('<span class="count">2</span>', false); // header badge

        $item = $cart->items()->first();
        $this->post(route('customer.cart.store', $product), ['quantity' => 2])->assertSessionHas('error'); // only 3 in stock
        $this->put(route('customer.cart.update', $item), ['quantity' => 3])->assertSessionHas('success');
        $this->assertSame(3, $item->fresh()->quantity);
        $this->delete(route('customer.cart.destroy', $item))->assertSessionHas('success');
        $this->assertSame(0, $cart->items()->count());
    }

    public function test_guest_carts_are_private_to_their_own_session(): void
    {
        $product = $this->product();
        $this->post(route('customer.cart.store', $product), ['quantity' => 1]);
        $item = Cart::first()->items()->first();

        // Another visitor (a fresh session) and a signed-in customer cannot see or touch it.
        $this->flushSession();
        $this->get(route('customer.cart.index'))->assertOk()->assertDontSee('Oak Sofa');
        $this->put(route('customer.cart.update', $item), ['quantity' => 4])->assertNotFound();
        $this->delete(route('customer.cart.destroy', $item))->assertNotFound();
        $this->actingAs($this->customer())->delete(route('customer.cart.destroy', $item))->assertNotFound();
        $this->assertSame(1, $item->fresh()->quantity);

        // A guessed token in the session of a signed-in customer is not a way in either.
        $this->assertSame(1, Cart::count());
    }

    public function test_admins_and_sellers_still_cannot_use_the_cart(): void
    {
        $product = $this->product();
        foreach (['admin', 'vendor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user)->get(route('customer.cart.index'))->assertForbidden();
            $this->actingAs($user)->post(route('customer.cart.store', $product), ['quantity' => 1])->assertForbidden();
        }
        $this->assertSame(0, Cart::count());
    }

    public function test_guest_checks_out_without_registering_and_the_order_is_placed(): void
    {
        $product = $this->product();
        $this->get(route('checkout.guest'))->assertRedirect(route('customer.cart.index')); // nothing in the cart yet
        $this->post(route('customer.cart.store', $product), ['quantity' => 2]);

        $this->get(route('checkout.guest'))->assertOk()->assertSee('Checkout as a guest')->assertSee('Oak Sofa')->assertSee('Maharashtra');
        $this->post(route('checkout.guest.store'), $this->details(['email' => 'Asha@Example.com']))->assertRedirect(route('customer.checkout.index'));

        $user = User::where('email', 'asha@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->is_guest);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertSame('Asha Mehta', $user->name);
        $this->assertSame('9999999999', $user->profile->phone);
        $address = $user->addresses()->first();
        $this->assertTrue($address->is_default);
        $this->assertSame('Pune', $address->city);

        // The guest cart followed them into the account, and the normal checkout takes over.
        $this->assertSame(2, Cart::where('user_id', $user->id)->first()->items()->first()->quantity);
        $this->assertSame(0, Cart::whereNull('user_id')->count());
        $this->get(route('customer.checkout.index'))->assertOk()->assertSee('12 MG Road')->assertSee('Oak Sofa');
        $this->post(route('customer.checkout.store'), ['shipping_address_id' => $address->id, 'billing_address_id' => $address->id, 'payment_method' => 'cod'])
            ->assertRedirect(route('customer.orders.show', Order::first()));

        $order = Order::first();
        $this->assertSame($user->id, $order->user_id);
        $this->assertEquals(2000 + 360 + 149 + 26.82, $order->total);
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->get(route('customer.orders.show', $order))->assertOk()->assertSee('You checked out as a guest');
    }

    public function test_guest_details_are_validated_and_a_chosen_password_makes_a_normal_account(): void
    {
        $this->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);

        $this->post(route('checkout.guest.store'), $this->details(['state' => 'Atlantis', 'email' => 'nope', 'first_name' => '']))
            ->assertSessionHasErrors(['state', 'email', 'first_name']);
        $this->post(route('checkout.guest.store'), $this->details(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post(route('checkout.guest.store'), $this->details(['password' => 'long-enough-1', 'password_confirmation' => 'different']))->assertSessionHasErrors('password');
        $this->assertSame(0, User::count());
        $this->assertGuest();

        $this->post(route('checkout.guest.store'), $this->details(['password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1']))
            ->assertRedirect(route('customer.checkout.index'));
        $user = User::first();
        $this->assertFalse($user->is_guest);
        $this->assertTrue(Hash::check('long-enough-1', $user->password));
    }

    public function test_an_existing_email_is_never_taken_over_and_the_cart_survives_signing_in(): void
    {
        $existing = $this->customer(['email' => 'asha@example.com', 'password' => Hash::make('their-password')]);
        $old = $this->product(['name' => 'Old Lamp', 'stock_quantity' => 2]);
        Cart::create(['user_id' => $existing->id, 'status' => 'active'])->items()->create(['product_id' => $old->id, 'quantity' => 1, 'unit_price' => 1000]);

        $sofa = $this->product();
        $this->post(route('customer.cart.store', $sofa), ['quantity' => 1]);
        $this->post(route('customer.cart.store', $old), ['quantity' => 2]);

        $this->post(route('checkout.guest.store'), $this->details(['email' => 'ASHA@example.com', 'password' => 'attacker-pass-1', 'password_confirmation' => 'attacker-pass-1']))
            ->assertSessionHasErrors('email')->assertSessionHas('existing_account');
        $this->assertGuest();
        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('their-password', $existing->fresh()->password));
        $this->assertSame(0, $existing->addresses()->count());
        $this->get(route('checkout.guest'))->assertOk()->assertSee(route('password.request'), false);

        // Signing in merges the guest cart into theirs: quantities add up, capped at the stock on hand.
        $this->post(route('login.post'), ['email' => 'asha@example.com', 'password' => 'their-password']);
        $this->assertAuthenticatedAs($existing);
        $items = Cart::where('user_id', $existing->id)->first()->items()->pluck('quantity', 'product_id');
        $this->assertSame(1, $items[$sofa->id]);
        $this->assertSame(2, $items[$old->id]); // 1 + 2 would be 3, but only 2 exist
        $this->assertSame(1, Cart::count());
    }

    public function test_signed_in_customers_skip_the_guest_step(): void
    {
        $this->actingAs($this->customer())->get(route('checkout.guest'))->assertRedirect();
        $this->actingAs($this->customer())->post(route('checkout.guest.store'), $this->details())->assertRedirect();
        $this->assertSame(0, User::where('email', 'asha@example.com')->count());
    }

    public function test_a_guest_account_sets_a_password_without_knowing_the_random_one(): void
    {
        $this->post(route('customer.cart.store', $this->product()), ['quantity' => 1]);
        $this->post(route('checkout.guest.store'), $this->details());
        $user = User::first();

        $this->get(route('customer.profile.edit'))->assertOk()->assertSee('Create a password')->assertDontSee('Current password');
        $this->put(route('customer.password.update'), ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->put(route('customer.password.update'), ['password' => 'my-new-pass-1', 'password_confirmation' => 'my-new-pass-1'])->assertSessionHas('success');
        $user->refresh();
        $this->assertFalse($user->is_guest);
        $this->assertTrue(Hash::check('my-new-pass-1', $user->password));

        // From now on it is a normal account: changing the password needs the current one.
        $this->get(route('customer.profile.edit'))->assertOk()->assertSee('Change password')->assertSee('Current password');
        $this->put(route('customer.password.update'), ['password' => 'another-pass-2', 'password_confirmation' => 'another-pass-2'])->assertSessionHasErrors('current_password');
        $this->put(route('customer.password.update'), ['current_password' => 'wrong', 'password' => 'another-pass-2', 'password_confirmation' => 'another-pass-2'])->assertSessionHasErrors('current_password');
        $this->put(route('customer.password.update'), ['current_password' => 'my-new-pass-1', 'password' => 'another-pass-2', 'password_confirmation' => 'another-pass-2'])->assertSessionHas('success');
        $this->assertTrue(Hash::check('another-pass-2', $user->fresh()->password));
    }

    public function test_forgot_password_emails_a_link_that_sets_a_new_password(): void
    {
        Notification::fake();
        $user = $this->customer(['email' => 'asha@example.com', 'is_guest' => true]);

        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk();

        // Same reply for an unknown address, and nothing is sent to it.
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('status');
        Notification::assertNothingSent();
        $this->post(route('password.email'), ['email' => 'Asha@Example.com'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => 'asha@example.com']))->assertOk()->assertSee('asha@example.com');
        $this->post(route('password.update'), ['token' => 'wrong-token', 'email' => 'asha@example.com', 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1'])
            ->assertSessionHasErrors('email');
        $this->post(route('password.update'), ['token' => $token, 'email' => 'asha@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post(route('password.update'), ['token' => $token, 'email' => 'asha@example.com', 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1'])
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-1', $user->password));
        $this->assertFalse($user->is_guest);
        // The link works once.
        $this->post(route('password.update'), ['token' => $token, 'email' => 'asha@example.com', 'password' => 'second-try-pass-2', 'password_confirmation' => 'second-try-pass-2'])
            ->assertSessionHasErrors('email');
        $this->post(route('login.post'), ['email' => 'asha@example.com', 'password' => 'brand-new-pass-1']);
        $this->assertAuthenticatedAs($user);
    }
}

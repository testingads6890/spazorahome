<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Compare;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountPagesTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('customer');

        return $u;
    }

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas']);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Oak Sofa', 'slug' => 'oak-' . uniqid(), 'description' => 'x',
            'sku' => 'S-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true,
        ], $o));
    }

    private function purchased(User $u, Product $p): void
    {
        $o = Order::create(['user_id' => $u->id, 'order_number' => 'O-' . uniqid(), 'status' => 'delivered', 'subtotal' => 1, 'total' => 1, 'currency' => 'INR']);
        $o->items()->create(['product_id' => $p->id, 'product_name' => $p->name, 'product_sku' => $p->sku, 'quantity' => 1, 'unit_price' => 1, 'subtotal' => 1]);
    }

    public function test_every_account_page_uses_the_account_layout_with_navigation(): void
    {
        $u = $this->customer();
        $address = Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India']);
        $ticket = SupportTicket::create(['user_id' => $u->id, 'ticket_number' => 'TKT-1', 'subject' => 'Help', 'description' => 'Need help', 'category' => 'order', 'priority' => 'low']);
        $p = $this->product();
        $review = Review::create(['user_id' => $u->id, 'product_id' => $p->id, 'rating' => 4, 'comment' => 'ok']);

        $pages = [
            route('customer.addresses.index'), route('customer.addresses.create'), route('customer.addresses.edit', $address),
            route('customer.profile.edit'), route('customer.professional-profile.edit'),
            route('customer.reviews.index'), route('customer.reviews.create'), route('customer.reviews.edit', $review),
            route('customer.support-tickets.index'), route('customer.support-tickets.create'), route('customer.support-tickets.show', $ticket),
            route('customer.wishlists.index'), route('customer.compare.index'),
        ];
        foreach ($pages as $url) {
            $this->actingAs($u)->get($url)->assertOk()->assertSee('account-nav', false)->assertSee('Order Return Requests')->assertSee('Logout');
        }
    }

    public function test_ticket_page_hides_internal_staff_notes_from_the_customer(): void
    {
        $u = $this->customer();
        $staff = User::factory()->create();
        $t = SupportTicket::create(['user_id' => $u->id, 'ticket_number' => 'TKT-2', 'subject' => 'Broken', 'description' => 'It arrived broken', 'category' => 'product', 'priority' => 'high']);
        $t->messages()->create(['user_id' => $staff->id, 'type' => 'chat', 'message' => 'We will replace it', 'is_internal' => false]);
        $t->messages()->create(['user_id' => $staff->id, 'type' => 'note', 'message' => 'Customer looks like a fraud, deny', 'is_internal' => true]);

        $this->actingAs($u)->get(route('customer.support-tickets.show', $t))->assertOk()
            ->assertSee('It arrived broken')->assertSee('We will replace it')->assertSee('Support team')
            ->assertDontSee('looks like a fraud');
    }

    public function test_closed_ticket_offers_no_reply_form(): void
    {
        $u = $this->customer();
        $t = SupportTicket::create(['user_id' => $u->id, 'ticket_number' => 'TKT-3', 'subject' => 'Done', 'description' => 'd', 'category' => 'other', 'priority' => 'low', 'status' => 'resolved', 'resolution' => 'Refunded']);

        $this->actingAs($u)->get(route('customer.support-tickets.show', $t))->assertOk()->assertSee('Refunded')->assertDontSee('Send reply');
    }

    public function test_reviews_list_shows_status_and_the_form_has_rating_title_and_comment(): void
    {
        $u = $this->customer();
        $p = $this->product();
        $this->purchased($u, $p);

        $this->actingAs($u)->get(route('customer.reviews.create'))->assertOk()->assertSee('Oak Sofa')->assertSee('name="title"', false)->assertSee('name="rating"', false);
        $this->actingAs($u)->post(route('customer.reviews.store'), ['product_id' => $p->id, 'rating' => 5, 'title' => 'Great', 'comment' => 'Love it'])->assertRedirect(route('customer.reviews.index'));
        $this->actingAs($u)->get(route('customer.reviews.index'))->assertOk()->assertSee('Great')->assertSee('Awaiting approval');

        $review = Review::firstOrFail();
        $review->update(['is_approved' => true]);
        $this->actingAs($u)->get(route('customer.reviews.index'))->assertSee('Published');
        $this->actingAs($u)->put(route('customer.reviews.update', $review), ['rating' => 3, 'title' => 'Ok now', 'comment' => 'Changed'])->assertRedirect();
        $this->assertSame('Ok now', $review->fresh()->title);
    }

    public function test_wishlist_shows_product_cards_and_supports_removal(): void
    {
        $u = $this->customer();
        $p = $this->product(['name' => 'Saved Chair']);
        Wishlist::create(['user_id' => $u->id, 'product_id' => $p->id]);

        $this->actingAs($u)->get(route('customer.wishlists.index'))->assertOk()->assertSee('Saved Chair')->assertSee('Remove from wishlist');
        $this->actingAs($u)->delete(route('customer.wishlists.destroy', $p));
        $this->actingAs($u)->get(route('customer.wishlists.index'))->assertSee('Your wishlist is empty');
    }

    public function test_compare_table_lines_products_up_and_lets_you_remove_them(): void
    {
        $u = $this->customer();
        $a = $this->product(['name' => 'Alpha', 'brand' => 'Acme', 'material' => 'Oak', 'price' => 900]);
        $b = $this->product(['name' => 'Beta', 'brand' => 'Zed', 'material' => 'Oak', 'price' => 1200]);
        foreach ([$a, $b] as $p) {
            Compare::create(['user_id' => $u->id, 'product_id' => $p->id]);
        }

        $this->actingAs($u)->get(route('customer.compare.index'))->assertOk()
            ->assertSeeInOrder(['Alpha', 'Beta'])->assertSee('₹900')->assertSee('₹1,200')->assertSee('Acme')->assertSee('Zed')->assertSee('Add to cart');
        $this->actingAs($u)->delete(route('customer.compare.destroy', $a));
        $this->assertSame(1, Compare::count());
        $this->actingAs($u)->delete(route('customer.compare.clear'));
        $this->actingAs($u)->get(route('customer.compare.index'))->assertSee('Nothing to compare yet');
    }

    public function test_addresses_page_lists_default_badge_and_empty_state(): void
    {
        $u = $this->customer();
        $this->actingAs($u)->get(route('customer.addresses.index'))->assertOk()->assertSee('No addresses yet');

        Address::create(['user_id' => $u->id, 'type' => 'shipping', 'first_name' => 'Asha', 'last_name' => 'K', 'address_line_1' => '1 Rd', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001', 'country' => 'India', 'is_default' => true]);
        $this->actingAs($u)->get(route('customer.addresses.index'))->assertOk()->assertSee('Asha K')->assertSee('Default');
    }
}

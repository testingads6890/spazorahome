<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_customer_can_update_profile_with_form_fields(): void
    {
        $user = $this->actor('customer');

        $this->actingAs($user)->get(route('customer.profile.edit'))->assertOk();
        $this->actingAs($user)->put(route('customer.profile.update'), [
            'name' => 'New Name', 'email' => 'new@example.com', 'phone' => '9999999999',
            'date_of_birth' => '1990-01-01', 'gender' => 'female',
        ])->assertRedirect(route('customer.profile.edit'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('9999999999', $user->profile()->first()->phone);
        $this->actingAs($user)->get(route('customer.profile.edit'))->assertOk()->assertSee('9999999999');
    }

    public function test_profile_email_must_be_unique(): void
    {
        $other = $this->actor('customer');
        $user = $this->actor('customer');

        $this->actingAs($user)->put(route('customer.profile.update'), ['name' => 'X', 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_customer_can_save_professional_profile(): void
    {
        $user = $this->actor('customer');

        $this->actingAs($user)->get(route('customer.professional-profile.edit'))->assertOk();
        $this->actingAs($user)->post(route('customer.professional-profile.store'), [
            'company_name' => 'Studio', 'experience_years' => 5, 'specialization' => 'Living rooms', 'website' => 'https://example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $user->fresh()->professionalProfile->experience_years);
    }

    public function test_customer_can_open_ticket_and_reply_but_not_see_others(): void
    {
        [$owner, $other] = [$this->actor('customer'), $this->actor('customer')];

        $this->actingAs($owner)->get(route('customer.support-tickets.create'))->assertOk();
        $response = $this->actingAs($owner)->post(route('customer.support-tickets.store'), [
            'subject' => 'Broken leg', 'description' => 'Chair arrived broken', 'category' => 'product', 'priority' => 'high',
        ]);
        $ticket = SupportTicket::firstOrFail();
        $response->assertRedirect(route('customer.support-tickets.show', $ticket));

        $this->actingAs($owner)->get(route('customer.support-tickets.show', $ticket))->assertOk()->assertSee('Broken leg');
        $this->actingAs($owner)->post(route('customer.support-tickets.message', $ticket), ['message' => 'Any update?'])->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->messages()->count());

        $this->actingAs($other)->get(route('customer.support-tickets.show', $ticket))->assertForbidden();
        $this->actingAs($other)->post(route('customer.support-tickets.message', $ticket), ['message' => 'hi'])->assertForbidden();
        $this->actingAs($owner)->get(route('customer.support-tickets.index'))->assertOk();
    }

    public function test_vendor_can_add_and_remove_staff_only_for_own_vendor(): void
    {
        $vu = $this->actor('vendor');
        $vendor = Vendor::create(['user_id' => $vu->id, 'business_name' => 'A', 'slug' => 'a', 'email' => 'a@e.com', 'status' => 'approved', 'is_active' => true]);
        $vu2 = $this->actor('vendor');
        Vendor::create(['user_id' => $vu2->id, 'business_name' => 'B', 'slug' => 'b', 'email' => 'b@e.com', 'status' => 'approved', 'is_active' => true]);
        $member = User::factory()->create();

        $this->actingAs($vu)->get(route('vendor.staff.create'))->assertOk();
        $this->actingAs($vu)->post(route('vendor.staff.store'), ['name' => 'Member', 'email' => $member->email, 'role' => 'staff'])
            ->assertRedirect(route('vendor.staff.index'));
        $this->actingAs($vu)->post(route('vendor.staff.store'), ['name' => 'Member', 'email' => $member->email, 'role' => 'staff']);
        $this->assertSame(1, VendorUser::where('vendor_id', $vendor->id)->count());
        $this->actingAs($vu)->get(route('vendor.staff.index'))->assertOk()->assertSee($member->email);

        $link = VendorUser::first();
        $this->actingAs($vu2)->delete(route('vendor.staff.destroy', $link))->assertForbidden();
        $this->actingAs($vu)->post(route('vendor.staff.store'), ['name' => 'Me', 'email' => $vu->email, 'role' => 'staff'])->assertSessionHasErrors('email');
        $this->actingAs($vu)->delete(route('vendor.staff.destroy', $link))->assertRedirect();
        $this->assertSame(0, VendorUser::count());
    }

    public function test_wishlist_and_compare_add_and_remove(): void
    {
        $user = $this->actor('customer');
        $category = \App\Models\Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = \App\Models\Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('customer.wishlists.store', $product))->assertSessionHas('success');
        $this->actingAs($user)->post(route('customer.wishlists.store', $product));
        $this->assertSame(1, $user->wishlists()->count());
        $this->actingAs($user)->get(route('customer.wishlists.index'))->assertOk()->assertSee('Sofa');
        $this->actingAs($user)->delete(route('customer.wishlists.destroy', $product));
        $this->assertSame(0, $user->wishlists()->count());

        $this->actingAs($user)->post(route('customer.compare.store', $product));
        $this->actingAs($user)->get(route('customer.compare.index'))->assertOk()->assertSee('Sofa');
        $this->actingAs($user)->delete(route('customer.compare.clear'));
        $this->assertSame(0, \App\Models\Compare::count());
    }
}

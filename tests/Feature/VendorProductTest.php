<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorProductTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function vendor(User $user): Vendor
    {
        return Vendor::create([
            'user_id' => $user->id, 'business_name' => 'V' . $user->id, 'slug' => 'v' . $user->id,
            'email' => "v{$user->id}@example.com", 'status' => 'approved', 'is_active' => true,
        ]);
    }

    private function payload(array $o = []): array
    {
        return array_merge($this->dispatchLocation(), [
            'category_id' => Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas'])->id,
            'name' => 'Oak Table', 'sku' => 'OAK-1', 'tax_rate' => 12, 'hsn_code' => '94036000', 'price' => 500, 'stock_quantity' => 3, 'description' => 'Solid oak',
        ], $o);
    }

    public function test_new_vendor_product_is_hidden_until_admin_approves(): void
    {
        $user = $this->actor('vendor');
        $vendor = $this->vendor($user);

        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['is_featured' => 1, 'vendor_status' => 'approved']))
            ->assertRedirect(route('vendor.products.index'));

        $product = Product::first();
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame('pending', $product->vendor_status);
        $this->assertFalse($product->is_featured);
        $this->assertSame('pending', VendorProduct::first()->status);
        $this->get(route('products.show', $product))->assertNotFound();

        $this->actingAs($this->actor('admin'))->post(route('admin.vendor-products.approve', VendorProduct::first()));
        $this->assertSame('approved', $product->fresh()->vendor_status);
        $this->get(route('products.show', $product))->assertOk();
    }

    public function test_vendor_cannot_edit_or_delete_another_vendors_product(): void
    {
        [$u1, $u2] = [$this->actor('vendor'), $this->actor('vendor')];
        $this->vendor($u1);
        $this->vendor($u2);

        $this->actingAs($u1)->post(route('vendor.products.store'), $this->payload());
        $product = Product::first();

        $this->actingAs($u1)->get(route('vendor.products.edit', $product))->assertOk();
        $this->actingAs($u2)->get(route('vendor.products.edit', $product))->assertForbidden();
        $this->actingAs($u2)->put(route('vendor.products.update', $product), $this->payload(['price' => 1]))->assertForbidden();
        $this->actingAs($u2)->delete(route('vendor.products.destroy', $product))->assertForbidden();
        $this->assertEquals(500, $product->fresh()->price);
    }

    public function test_vendor_can_update_own_product(): void
    {
        $user = $this->actor('vendor');
        $this->vendor($user);
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload());
        $product = Product::first();

        $this->actingAs($user)->put(route('vendor.products.update', $product), $this->payload(['price' => 450, 'is_active' => 0]))
            ->assertRedirect(route('vendor.products.index'));

        $this->assertEquals(450, $product->fresh()->price);
        $this->assertFalse($product->fresh()->is_active);
        $this->actingAs($user)->get(route('vendor.products.index'))->assertOk()->assertSee('Oak Table');
    }

    public function test_vendor_sets_gst_from_allowed_slabs_at_listing_and_cannot_change_it_later(): void
    {
        $user = $this->actor('vendor');
        $this->vendor($user);

        $this->actingAs($user)->get(route('vendor.products.create'))->assertOk()->assertSee('name="tax_rate"', false);
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['tax_rate' => 7]))->assertSessionHasErrors('tax_rate');
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['tax_rate' => 0]))->assertSessionHasNoErrors();
        $this->assertSame(0, Product::count() - 1);
        $product = Product::firstOrFail();
        $this->assertEquals(0, $product->tax_rate);

        $this->actingAs($user)->put(route('vendor.products.update', $product), $this->payload(['tax_rate' => 28, 'is_active' => 1]));
        $this->assertEquals(0, $product->fresh()->tax_rate);

        $this->actingAs($this->actor('admin'))->get(route('admin.vendor-products.index'))->assertOk()->assertSee('0%', false);
    }

    public function test_vendor_must_give_a_valid_hsn_code_when_listing(): void
    {
        $user = $this->actor('vendor');
        $this->vendor($user);

        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['hsn_code' => '']))->assertSessionHasErrors('hsn_code');
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['hsn_code' => '123']))->assertSessionHasErrors('hsn_code');
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['hsn_code' => 'ABCD1234']))->assertSessionHasErrors('hsn_code');
        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['hsn_code' => '9403']))->assertSessionHasNoErrors();
        $this->assertSame('9403', Product::first()->hsn_code);
    }

    public function test_vendor_gstin_is_validated_against_format_and_state(): void
    {
        $user = $this->actor('vendor');
        $this->vendor($user);
        $base = ['business_name' => 'Acme', 'state' => 'Maharashtra'];

        $this->actingAs($user)->get(route('vendor.profile.edit'))->assertOk()->assertSee('name="tax_number"', false);
        $this->actingAs($user)->put(route('vendor.profile.update'), $base + ['tax_number' => 'NOTAGSTIN'])->assertSessionHasErrors('tax_number');
        // Valid format but a Karnataka (29) GSTIN for a Maharashtra (27) vendor.
        $this->actingAs($user)->put(route('vendor.profile.update'), $base + ['tax_number' => '29AAAAA0000A1Z5'])->assertSessionHasErrors('tax_number');
        $this->actingAs($user)->put(route('vendor.profile.update'), array_merge($base, ['state' => 'Mumbai']))->assertSessionHasErrors('state');
        $this->actingAs($user)->put(route('vendor.profile.update'), $base + ['tax_number' => '27aaaaa0000a1z5'])->assertSessionHasNoErrors();
        $this->assertSame('27AAAAA0000A1Z5', $user->vendor()->first()->tax_number);
    }
}

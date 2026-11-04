<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_update_and_delete_a_product_using_the_form_fields(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);

        // Exactly the fields the create form submits.
        $form = $this->dispatchLocation() + [
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'Comfy',
            'short_description' => 'x', 'sku' => 'S-1', 'price' => 100, 'sale_price' => '', 'stock_quantity' => 4,
            'meta_title' => '', 'meta_description' => '', 'is_active' => 1, 'user_role' => 'interior',
            'brand' => 'Acme', 'is_featured' => 1, 'low_stock_threshold' => '',
        ];

        $this->actingAs($admin)->post(route('admin.products.store'), $form)->assertRedirect(route('admin.products.index'));
        $product = Product::first();
        $this->assertSame('interior', $product->user_role);
        $this->assertSame('Acme', $product->brand);
        $this->assertTrue($product->is_featured);

        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($form, ['price' => 90]))
            ->assertRedirect(route('admin.products.index'));
        $this->assertEquals(90, $product->fresh()->price);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertSoftDeleted($product);
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Beds', 'slug' => 'beds', 'status' => 1])
            ->assertRedirect(route('admin.categories.index'));
        $category = Category::where('slug', 'beds')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.categories.update', $category), ['name' => 'Beds & More', 'slug' => 'beds', 'status' => 1])
            ->assertRedirect(route('admin.categories.index'));
        $this->assertSame('Beds & More', $category->fresh()->name);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertSoftDeleted($category);
    }

    public function test_admin_can_set_and_clear_product_gst_rate(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $form = $this->dispatchLocation() + [
            'category_id' => $category->id, 'name' => 'Lamp', 'slug' => 'lamp', 'description' => 'x', 'sku' => 'L-1',
            'price' => 100, 'stock_quantity' => 4, 'is_active' => 1, 'tax_rate' => '12',
        ];

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('name="tax_rate"', false);
        $this->actingAs($admin)->post(route('admin.products.store'), $form)->assertRedirect(route('admin.products.index'));
        $product = Product::firstOrFail();
        $this->assertEquals(12, $product->tax_rate);
        $this->assertSame(12.0, $product->taxRate());

        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($form, ['tax_rate' => '']));
        $this->assertNull($product->fresh()->tax_rate);
        $this->assertSame((float) config('shop.tax_rate'), $product->fresh()->taxRate());

        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($form, ['tax_rate' => '150']))
            ->assertSessionHasErrors('tax_rate');
    }

    public function test_admin_can_set_hsn_code_and_bad_values_are_rejected(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $form = $this->dispatchLocation() + [
            'category_id' => $category->id, 'name' => 'Desk', 'slug' => 'desk', 'description' => 'x', 'sku' => 'D-1',
            'price' => 100, 'stock_quantity' => 4, 'is_active' => 1, 'hsn_code' => '94036000',
        ];

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('name="hsn_code"', false);
        $this->actingAs($admin)->post(route('admin.products.store'), $form)->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertSame('94036000', $product->hsn_code);

        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($form, ['hsn_code' => '12']))->assertSessionHasErrors('hsn_code');
        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($form, ['hsn_code' => '']));
        $this->assertNull($product->fresh()->hsn_code);
    }
}

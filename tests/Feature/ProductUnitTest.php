<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductUnitTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_all_default_units_are_offered(): void
    {
        $options = ProductUnit::options();
        foreach (['running_ft', 'rm', 'sqft', 'sqm', 'roll', 'box', 'pcs', 'set', 'pair'] as $key) {
            $this->assertArrayHasKey($key, $options);
        }
        $this->assertSame('Running ft', $options['running_ft']);
    }

    public function test_admin_and_seller_can_add_a_unit_with_its_inputs(): void
    {
        $response = $this->actingAs($this->user('admin'))->postJson(route('product-units.store'), ['label' => 'Pack of 10']);
        $response->assertCreated()->assertJsonPath('unit.label', 'Pack of 10');

        $unit = ProductUnit::where('key', 'pack_of_10')->firstOrFail();
        $this->assertSame('Pack of 10', $unit->label);
        $this->assertArrayHasKey('pack_of_10', ProductUnit::options());

        $this->actingAs($this->user('vendor'))->postJson(route('product-units.store'), ['label' => 'Sq yard'])->assertCreated();
        $this->assertArrayHasKey('sq_yard', ProductUnit::options());
    }

    public function test_adding_the_same_name_again_returns_the_existing_unit(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson(route('product-units.store'), ['label' => 'Sq ft'])->assertOk()->assertJsonPath('unit.key', 'sqft');
        $this->assertSame(9, ProductUnit::count());
    }

    public function test_customers_cannot_add_units_and_a_name_is_required(): void
    {
        $this->actingAs($this->user('customer'))->postJson(route('product-units.store'), ['label' => 'Bag'])->assertForbidden();
        $this->actingAs($this->user('admin'))->postJson(route('product-units.store'), ['label' => ''])->assertUnprocessable();
    }

    public function test_unit_numbers_are_saved_only_for_the_chosen_unit(): void
    {
        $data = $this->dispatchLocation();
        $category = \App\Models\Category::firstOrCreate(['slug' => 'beds'], ['name' => 'Beds', 'status' => true]);
        $payload = $data + [
            'category_id' => $category->id, 'name' => 'Unit Chair', 'slug' => 'unit-chair-' . uniqid(), 'description' => 'A chair with a unit.',
            'sku' => 'U-' . uniqid(), 'price' => 500, 'stock_quantity' => 2, 'is_active' => 1, 'user_role' => 'customer',
            'unit' => 'running_ft', 'unit_details' => ['length' => '8', 'width' => '2', 'height' => '3.5', 'measure' => 'ft', 'area' => '99'],
        ];
        $this->actingAs($this->user('admin'))->post(route('admin.products.store'), $payload)->assertSessionHasNoErrors();

        $product = Product::where('name', 'Unit Chair')->firstOrFail();
        $this->assertSame('running_ft', $product->unit);
        $this->assertSame(['length' => 8, 'width' => 2, 'height' => 3.5, 'measure' => 'ft'], $product->unit_details);
    }
}

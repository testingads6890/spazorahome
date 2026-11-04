<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Pincode;
use App\Models\Product;
use App\Models\User;
use App\Support\DeliveryArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryAreaTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $o = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'sofas'], ['name' => 'Sofas', 'status' => true]);

        return Product::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Area Chair', 'slug' => 'area-' . uniqid(), 'description' => 'd',
            'sku' => 'A-' . uniqid(), 'price' => 1000, 'stock_quantity' => 5, 'is_active' => true,
        ], $o));
    }

    public function test_product_without_a_location_is_delivered_across_india(): void
    {
        $this->assertTrue(DeliveryArea::canDeliver($this->product(), null));
    }

    public function test_product_with_a_location_needs_a_matching_pin(): void
    {
        $loc = $this->dispatchLocation();
        $p = $this->product(['country_id' => $loc['country_id'], 'state_id' => $loc['state_id'], 'district_id' => $loc['district_id'], 'city_id' => $loc['city_id'], 'pincode_id' => $loc['pincode_id']]);
        $pin = Pincode::find($loc['pincode_id']);

        $this->assertFalse(DeliveryArea::canDeliver($p, null));
        $this->assertTrue(DeliveryArea::canDeliver($p, $pin));
    }

    public function test_several_pincodes_for_one_product_and_city_or_state_matching(): void
    {
        $loc = $this->dispatchLocation();
        $second = Pincode::create(['city_id' => $loc['city_id'], 'code' => '302002', 'is_active' => true]);
        $other = Pincode::create(['city_id' => $loc['city_id'], 'code' => '302003', 'is_active' => true]);
        $p = $this->product(['country_id' => $loc['country_id'], 'pincode_id' => $loc['pincode_id']]);
        DB::table('product_pincode')->insert(['product_id' => $p->id, 'pincode_id' => $second->id]);

        $this->assertTrue(DeliveryArea::canDeliver($p, $second));
        $this->assertFalse(DeliveryArea::canDeliver($p, $other));

        $city = $this->product(['country_id' => $loc['country_id'], 'city_id' => $loc['city_id']]);
        $this->assertTrue(DeliveryArea::canDeliver($city, $other));

        $state = $this->product(['country_id' => $loc['country_id'], 'state_id' => $loc['state_id']]);
        $this->assertTrue(DeliveryArea::canDeliver($state, $other));
    }

    public function test_adding_an_undeliverable_product_to_the_cart_is_refused_until_a_matching_pin_is_saved(): void
    {
        $loc = $this->dispatchLocation();
        $p = $this->product(['country_id' => $loc['country_id'], 'pincode_id' => $loc['pincode_id']]);
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));

        $this->actingAs($user)->post(route('customer.cart.store', $p), ['quantity' => 1])
            ->assertSessionHas('error', __('shop.messages.not_deliverable_here'));
        $this->assertDatabaseCount('cart_items', 0);

        $this->actingAs($user)->post(route('delivery-area.store'), ['pincode' => '302001'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('customer.cart.store', $p), ['quantity' => 1])->assertSessionHas('success');
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_an_unknown_pin_is_reported_and_not_remembered(): void
    {
        $this->post(route('delivery-area.store'), ['pincode' => '999999'])->assertSessionHas('error');
        $this->assertNull(DeliveryArea::current());
        $this->post(route('delivery-area.store'), ['pincode' => 'abc'])->assertSessionHasErrors('pincode');
    }

    public function test_the_pin_can_be_cleared(): void
    {
        $this->dispatchLocation();
        $this->post(route('delivery-area.store'), ['pincode' => '302001']);
        $this->assertNotNull(DeliveryArea::current());
        $this->post(route('delivery-area.clear'))->assertSessionHas('success');
        $this->assertNull(DeliveryArea::current());
    }
}

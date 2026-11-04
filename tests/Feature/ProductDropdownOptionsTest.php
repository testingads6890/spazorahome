<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ProductChoices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Saves a product once for every option in the product form dropdowns, as admin and as seller. */
class ProductDropdownOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        $loc = $this->dispatchLocation();
        $category = \App\Models\Category::firstOrCreate(['slug' => 'beds'], ['name' => 'Beds', 'status' => true]);

        return $o + $loc + [
            'category_id' => $category->id, 'name' => 'Option Bed', 'slug' => 'option-bed-' . uniqid(), 'description' => 'Solid teak bed.',
            'sku' => 'OPT-' . uniqid(), 'price' => 20000, 'stock_quantity' => 3, 'is_active' => 1, 'user_role' => 'customer',
            'tax_rate' => 18, 'hsn_code' => '9403',
        ];
    }

    private function seller(): User
    {
        Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('vendor');
        Vendor::create(['user_id' => $user->id, 'business_name' => 'Opt Shop', 'slug' => 'opt-shop', 'email' => 'opt@example.com', 'status' => 'approved', 'is_active' => true]);

        return $user->fresh();
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function colourOptions(): array
    {
        $out = [];
        foreach (ProductChoices::COLOURS as $c) {
            $out[$c] = [$c, ''];
        }
        $out['Other (typed)'] = [ProductChoices::OTHER, 'Rose gold'];

        return $out;
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function materialOptions(): array
    {
        $out = [];
        foreach (ProductChoices::MATERIALS as $m) {
            $out[$m] = [$m, ''];
        }
        $out['Other (typed)'] = [ProductChoices::OTHER, 'Recycled teak'];

        return $out;
    }

    /** @return array<string, array{0: string}> */
    public static function serviceOptions(): array
    {
        return array_map(fn ($k) => [$k], array_combine(array_keys(ProductChoices::SERVICE_TYPES), array_keys(ProductChoices::SERVICE_TYPES)));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('colourOptions')]
    public function test_every_colour_option_saves_for_admin_and_seller(string $choice, string $other): void
    {
        $expected = $choice === ProductChoices::OTHER ? $other : $choice;
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload(['color' => $choice, 'color_other' => $other, 'sku' => 'A-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame($expected, Product::where('sku', 'like', 'A-%')->latest('id')->value('color'));

        $this->actingAs($this->seller())->post(route('vendor.products.store'), $this->payload(['color' => $choice, 'color_other' => $other, 'sku' => 'V-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame($expected, Product::where('sku', 'like', 'V-%')->latest('id')->value('color'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('materialOptions')]
    public function test_every_material_option_saves_for_admin_and_seller(string $choice, string $other): void
    {
        $expected = $choice === ProductChoices::OTHER ? $other : $choice;
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload(['material' => $choice, 'material_other' => $other, 'sku' => 'M-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame($expected, Product::where('sku', 'like', 'M-%')->latest('id')->value('material'));

        $this->actingAs($this->seller())->post(route('vendor.products.store'), $this->payload(['material' => $choice, 'material_other' => $other, 'sku' => 'MV-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame($expected, Product::where('sku', 'like', 'MV-%')->latest('id')->value('material'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serviceOptions')]
    public function test_every_assembly_option_saves_for_admin_and_seller(string $service): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload(['service_type' => $service, 'sku' => 'S-' . uniqid()]))->assertSessionHasNoErrors();
        $this->actingAs($this->seller())->post(route('vendor.products.store'), $this->payload(['service_type' => $service, 'sku' => 'SV-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame(2, Product::whereIn('sku', [Product::where('sku', 'like', 'S-%')->value('sku'), Product::where('sku', 'like', 'SV-%')->value('sku')])->count());
    }

    public function test_every_location_level_saves_for_admin_and_seller(): void
    {
        $loc = $this->dispatchLocation();
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload($loc + ['sku' => 'L-' . uniqid()]))->assertSessionHasNoErrors();
        $this->actingAs($this->seller())->post(route('vendor.products.store'), $this->payload($loc + ['sku' => 'LV-' . uniqid()]))->assertSessionHasNoErrors();
        $this->assertSame(2, Product::whereNotNull('pincode_id')->count());
    }

    public function test_seller_cannot_save_the_location_chain_with_a_wrong_district(): void
    {
        $loc = $this->dispatchLocation();
        $otherState = \App\Models\State::create(['country_id' => $loc['country_id'], 'name' => 'Other State', 'is_active' => true]);
        $other = \App\Models\District::create(['state_id' => $otherState->id, 'name' => 'Other District', 'is_active' => true]);
        // payload() keeps the keys given first, so this district overrides the default one.
        $data = $this->payload(['sku' => 'CH-' . uniqid(), 'district_id' => $other->id]);
        $this->actingAs($this->seller())->post(route('vendor.products.store'), $data)->assertSessionHasErrors('district_id');
    }

    public function test_several_pincodes_are_saved_to_the_product(): void
    {
        $loc = $this->dispatchLocation();
        $second = \App\Models\Pincode::create(['city_id' => $loc['city_id'], 'code' => '302010', 'is_active' => true]);
        $data = $this->payload($loc + ['sku' => 'PIN-' . uniqid(), 'pincode_ids' => [$loc['pincode_id'], $second->id], 'pincode_ids_sent' => 1]);
        $this->actingAs($this->admin())->post(route('admin.products.store'), $data)->assertSessionHasNoErrors();

        $product = Product::where('sku', $data['sku'])->firstOrFail();
        $this->assertEqualsCanonicalizing([$loc['pincode_id'], $second->id], DB::table('product_pincode')->where('product_id', $product->id)->pluck('pincode_id')->all());
    }
}

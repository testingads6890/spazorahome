<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\Pincode;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductFormFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function adminPayload(array $o = []): array
    {
        $category = Category::firstOrCreate(['slug' => 'beds'], ['name' => 'Beds', 'status' => true]);

        return $o + $this->dispatchLocation() + [
            'category_id' => $category->id, 'name' => 'Teak Bed', 'slug' => 'teak-bed', 'description' => 'Solid teak bed.',
            'sku' => 'TB-' . uniqid(), 'price' => 20000, 'stock_quantity' => 3, 'is_active' => 1, 'user_role' => 'customer',
        ];
    }

    private function sellerUser(): User
    {
        Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('vendor');
        Vendor::create(['user_id' => $user->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => $user->email, 'status' => 'approved', 'is_active' => true, 'commission_rate' => null]);

        return $user;
    }

    public function test_location_options_cascade_from_country_to_pincode(): void
    {
        $loc = $this->dispatchLocation();

        $this->getJson(route('location.options', ['level' => 'countries']))->assertOk()->assertJsonFragment(['name' => 'India']);
        $this->getJson(route('location.options', ['level' => 'states', 'parent' => $loc['country_id']]))->assertOk()->assertJsonFragment(['name' => 'Rajasthan']);
        $this->getJson(route('location.options', ['level' => 'districts', 'parent' => $loc['state_id']]))->assertOk()->assertJsonFragment(['name' => 'Jaipur']);
        $this->getJson(route('location.options', ['level' => 'cities', 'parent' => $loc['district_id']]))->assertOk()->assertJsonFragment(['name' => 'Jaipur']);
        $this->getJson(route('location.options', ['level' => 'pincodes', 'parent' => $loc['city_id']]))->assertOk()->assertJsonFragment(['name' => '302001']);
    }

    public function test_an_unknown_location_level_is_not_found(): void
    {
        $this->getJson('/location-options/planets')->assertNotFound();
    }

    public function test_the_india_seeder_creates_country_and_all_states(): void
    {
        $this->seed(\Database\Seeders\LocationMasterSeeder::class);
        $india = Country::where('name', 'India')->firstOrFail();
        $this->assertGreaterThanOrEqual(36, State::where('country_id', $india->id)->count());
        $this->assertSame('IN', $india->iso2);
    }

    public function test_only_the_country_is_required_for_admin_products(): void
    {
        $payload = $this->adminPayload();
        unset($payload['country_id'], $payload['state_id'], $payload['district_id'], $payload['city_id'], $payload['pincode_id']);
        $this->actingAs($this->admin())->post(route('admin.products.store'), $payload)->assertSessionHasErrors(['country_id']);
        $this->assertSame(0, Product::count());
    }

    public function test_only_the_country_is_required_for_seller_products(): void
    {
        $seller = $this->sellerUser();
        $payload = $this->adminPayload();
        unset($payload['country_id'], $payload['state_id'], $payload['district_id'], $payload['city_id'], $payload['pincode_id']);
        $this->actingAs($seller)->post(route('vendor.products.store'), $payload)->assertSessionHasErrors(['country_id']);
    }

    public function test_a_location_that_does_not_match_the_chain_is_refused(): void
    {
        $loc = $this->dispatchLocation();
        $otherState = State::create(['country_id' => $loc['country_id'], 'name' => 'Goa', 'is_active' => true]);
        $payload = $this->adminPayload(['state_id' => $otherState->id, 'district_id' => $loc['district_id']]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), $payload)->assertSessionHasErrors('district_id');
        $this->assertSame(0, Product::count());
    }

    public function test_colour_and_material_typed_under_other_are_saved_as_typed(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload([
            'color' => '__other', 'color_other' => 'Mustard yellow',
            'material' => '__other', 'material_other' => 'Recycled teak',
            'material_grade' => 'Grade A, 18 mm',
            'secondary_material' => 'Metal', 'secondary_material_grade' => 'Mild steel 2 mm',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('sku', 'like', 'TB-%')->firstOrFail();
        $this->assertSame('Mustard yellow', $product->color);
        $this->assertSame('Recycled teak', $product->material);
        $this->assertSame('Grade A, 18 mm', $product->material_grade);
        $this->assertSame('Metal', $product->secondary_material);
        $this->assertSame('Mild steel 2 mm', $product->secondary_material_grade);
    }

    public function test_other_needs_a_typed_value(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['color' => '__other', 'color_other' => '']))
            ->assertSessionHasErrors('color_other');
    }

    public function test_a_colour_from_the_list_is_saved_directly(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['color' => 'Walnut']))->assertSessionHasNoErrors();
        $this->assertSame('Walnut', Product::where('sku', 'like', 'TB-%')->firstOrFail()->color);
    }

    public function test_service_type_sets_the_assembly_and_installation_flags(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['service_type' => 'assembly_installation']))->assertSessionHasNoErrors();
        $product = Product::where('sku', 'like', 'TB-%')->firstOrFail();
        $this->assertSame('assembly_installation', $product->service_type);
        $this->assertTrue((bool) $product->assembly_required);
        $this->assertTrue((bool) $product->installation_included);
    }

    public function test_maximum_order_quantity_cannot_be_below_the_minimum(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['min_order_qty' => 5, 'max_order_qty' => 3]))
            ->assertSessionHasErrors('max_order_qty');
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['min_order_qty' => 1, 'max_order_qty' => 10]))
            ->assertSessionHasNoErrors();
        $this->assertSame(10, Product::where('sku', 'like', 'TB-%')->firstOrFail()->max_order_qty);
    }

    public function test_a_typed_category_is_created_live_for_admins_and_awaits_approval_for_sellers(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['category_id' => null, 'custom_category' => 'Jharokha Decor']))
            ->assertSessionHasNoErrors();
        $this->assertTrue(Category::where('name', 'Jharokha Decor')->where('status', true)->exists());

        $seller = $this->sellerUser();
        $this->actingAs($seller)->post(route('vendor.products.store'), $this->adminPayload(['category_id' => null, 'custom_category' => 'Brass Lamps', 'sku' => 'BL-1', 'tax_rate' => 18, 'hsn_code' => '9405']))
            ->assertSessionHasNoErrors();
        $this->assertFalse((bool) Category::where('name', 'Brass Lamps')->value('status'));
        $this->assertNotNull(Product::where('sku', 'BL-1')->value('category_id'));
    }

    public function test_typing_an_existing_category_name_reuses_it(): void
    {
        $existing = Category::create(['name' => 'Sofas', 'slug' => 'sofas', 'status' => true]);
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->adminPayload(['category_id' => null, 'custom_category' => 'sofas']))
            ->assertSessionHasNoErrors();
        $this->assertSame($existing->id, Product::where('sku', 'like', 'TB-%')->firstOrFail()->category_id);
        $this->assertSame(1, Category::where('slug', 'like', 'sofas%')->count());
    }

    public function test_the_admin_and_seller_product_forms_show_the_new_fields(): void
    {
        $this->actingAs($this->admin())->get(route('admin.products.create'))->assertOk()
            ->assertSee('Maximum order quantity')->assertSee('Dispatch time (days)')->assertSee('Assembly / Installation')
            ->assertSee('Primary material grade')->assertSee('Secondary material grade')->assertSee('Dispatch location')
            ->assertSee('Or type a new category name')->assertSee('Other (type it)');

        $this->actingAs($this->sellerUser())->get(route('vendor.products.create'))->assertOk()
            ->assertSee('Maximum order quantity')->assertSee('Dispatch location')->assertSee('Or type a new category name');
    }

    public function test_the_location_import_command_loads_districts_cities_and_pincodes(): void
    {
        $this->seed(\Database\Seeders\LocationMasterSeeder::class);
        $path = tempnam(sys_get_temp_dir(), 'loc') . '.csv';
        file_put_contents($path, "state,district,city,pincode\nRajasthan,Jaipur,Jaipur,302001\nRajasthan,Jaipur,Jaipur,302002\nRajasthan,Jodhpur,Jodhpur,342001\nAtlantis,X,Y,302001\nRajasthan,Jaipur,Jaipur,12\n");

        $this->artisan('location:import', ['file' => $path])->assertSuccessful();

        $this->assertSame(2, District::count());
        $this->assertSame(2, City::count());
        $this->assertSame(3, Pincode::count());
        @unlink($path);
    }
}

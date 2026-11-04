<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ProductExtraFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The extra product details from the vendor data sheet, on the seller's add / edit product form. */
class VendorProductExtraFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('vendor');
        Vendor::create(['user_id' => $this->user->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'a@e.com', 'status' => 'approved', 'is_active' => true]);
    }

    private function payload(array $o = []): array
    {
        return array_merge($this->dispatchLocation(), [
            'category_id' => Category::firstOrCreate(['slug' => 'beds'], ['name' => 'Beds'])->id,
            'name' => 'Teak Bed', 'sku' => 'BED-1', 'tax_rate' => 18, 'hsn_code' => '94035000', 'price' => 20000, 'stock_quantity' => 3, 'description' => 'Solid teak bed',
        ], $o);
    }

    public function test_every_detail_from_the_sheet_is_saved(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload([
            'vendor_sku' => 'ACME-77', 'manufacturer' => 'Acme Works', 'model_no' => 'T-9', 'collection_series' => 'Heritage',
            'material_grade' => 'Teak, grade A, 18 mm', 'secondary_material' => 'Brass', 'colour_code' => 'WAL-12',
            'finish_type' => 'PU', 'finish_code' => 'PU-3', 'country_of_origin' => 'India', 'features' => "Soft close\nHydraulic lift",
            'mrp' => 30000, 'unit' => 'set', 'lead_time_days' => 21, 'made_to_order' => 1, 'is_customizable' => 1,
            'assembly_required' => 1, 'assembly_time' => '45 minutes', 'assembly_difficulty' => 'Medium', 'assembly_instructions' => 'Two people',
            'installation_included' => 0, 'installation_charges' => 1500.50,
            'warranty_months' => 36, 'warranty_exclusions' => 'Water damage', 'return_eligible' => 0, 'return_policy_days' => 7,
            'return_policy' => 'Custom sizes are final sale', 'replacement_policy' => 'Free replacement if defective', 'damage_policy' => 'Report in 48 hours',
            'packed_length_cm' => 210, 'packed_width_cm' => 180.5, 'packed_height_cm' => 40, 'packaging_weight' => 95.25, 'packaging_quantity' => 3,
            'dispatch_from' => 'Jaipur, Rajasthan', 'shipping_restrictions' => 'No island delivery', 'is_fragile' => 1, 'is_bulky' => 1,
            'catalogue_url' => 'https://acme.example/catalogue.pdf', 'cad_url' => 'https://acme.example/cad.dwg', 'image_folder_url' => 'https://acme.example/images',
            'vendor_notes' => 'Photos in the folder are final',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('vendor.products.index'));

        $p = Product::first();
        $this->assertSame('ACME-77', $p->vendor_sku);
        $this->assertSame('Heritage', $p->collection_series);
        $this->assertSame('30000.00', $p->mrp);
        $this->assertSame('set', $p->unit);
        $this->assertSame(21, $p->lead_time_days);
        $this->assertTrue($p->made_to_order && $p->is_customizable && $p->assembly_required && $p->is_fragile && $p->is_bulky);
        $this->assertFalse($p->installation_included);
        $this->assertFalse($p->return_eligible);
        $this->assertSame(36, (int) $p->warranty_months);
        $this->assertEquals(1500.50, $p->installation_charges);
        $this->assertEquals(180.5, $p->packed_width_cm);
        $this->assertSame('3', (string) $p->packaging_quantity);
        $this->assertSame('Jaipur, Rajasthan', $p->dispatch_from);
        $this->assertSame('https://acme.example/cad.dwg', $p->cad_url);
        $this->assertSame('Report in 48 hours', $p->damage_policy);
    }

    public function test_all_of_them_are_optional(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload())->assertSessionHasNoErrors();
        $p = Product::first();
        $this->assertNull($p->mrp);
        $this->assertNull($p->category_attributes);
        $this->assertNull($p->lead_time_days);
    }

    public function test_bad_values_are_refused(): void
    {
        $bad = [
            'mrp' => 100,                       // lower than the price
            'lead_time_days' => 5000,
            'unit' => 'bushel',
            'assembly_difficulty' => 'Impossible',
            'catalogue_url' => 'not a link',
            'packed_length_cm' => -4,
            'packaging_quantity' => 'many',
            'warranty_months' => 'forever',
            'detail_group' => 'SPACESHIP',
        ];
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload($bad))->assertSessionHasErrors(array_keys($bad));
        $this->assertSame(0, Product::count());
    }

    public function test_details_for_one_kind_of_product_keep_only_the_chosen_group(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload([
            'detail_group' => 'BED',
            'attr' => [
                'BED' => ['bed_style' => 'Platform', 'bed_size' => 'King', 'headboard_included' => 'Yes', 'drawer_soft_close' => 'No', 'not_a_real_detail' => 'x'],
                'LIGHTING' => ['wattage' => '12 W'],   // another group: ignored
            ],
        ]))->assertSessionHasNoErrors();

        $attrs = Product::first()->category_attributes;
        $this->assertSame('BED', $attrs['group']);
        $this->assertSame(['bed_style' => 'Platform', 'bed_size' => 'King', 'headboard_included' => 'Yes', 'drawer_soft_close' => 'No'], $attrs['values']);
    }

    public function test_yes_no_details_only_accept_yes_or_no(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload([
            'detail_group' => 'BED', 'attr' => ['BED' => ['headboard_included' => 'Maybe']],
        ]))->assertSessionHasErrors('attr.BED.headboard_included');
        $this->assertSame(0, Product::count());
    }

    public function test_editing_keeps_what_the_form_did_not_send_and_clears_what_was_emptied(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload([
            'lead_time_days' => 14, 'dispatch_from' => 'Jaipur', 'made_to_order' => 1,
            'detail_group' => 'BED', 'attr' => ['BED' => ['bed_style' => 'Platform']],
        ]));
        $product = Product::first();

        // A quick edit that only sends the basics (older form, or a list's quick edit): nothing extra is lost.
        $this->actingAs($this->user)->put(route('vendor.products.update', $product), ['name' => 'Teak Bed Deluxe', 'price' => 21000, 'stock_quantity' => 2, 'description' => 'x'])->assertSessionHasNoErrors();
        $p = $product->fresh();
        $this->assertSame('Teak Bed Deluxe', $p->name);
        $this->assertSame(14, $p->lead_time_days);
        $this->assertSame('Jaipur', $p->dispatch_from);
        $this->assertTrue($p->made_to_order);
        $this->assertSame('Platform', $p->category_attributes['values']['bed_style']);

        // The full form with a field emptied and another kind of product chosen.
        $this->actingAs($this->user)->put(route('vendor.products.update', $product), $this->payload([
            'name' => 'Teak Bed Deluxe', 'lead_time_days' => '', 'dispatch_from' => 'Delhi', 'made_to_order' => 0,
            'detail_group' => 'SOFA', 'attr' => ['SOFA' => ['sofa_style' => 'L-shape']],
        ]))->assertSessionHasNoErrors();
        $p = $product->fresh();
        $this->assertNull($p->lead_time_days);
        $this->assertSame('Delhi', $p->dispatch_from);
        $this->assertFalse($p->made_to_order);
        $this->assertSame('SOFA', $p->category_attributes['group']);
        $this->assertArrayNotHasKey('bed_style', $p->category_attributes['values']);

        // Choosing "Not needed" removes the specifics.
        $this->actingAs($this->user)->put(route('vendor.products.update', $product), $this->payload(['name' => 'Teak Bed Deluxe', 'detail_group' => '']))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->category_attributes);
    }

    public function test_an_approved_listing_can_still_update_its_details(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload());
        $product = Product::first();
        $product->update(['vendor_status' => 'approved']);

        $this->actingAs($this->user)->put(route('vendor.products.update', $product), $this->payload(['name' => 'Teak Bed', 'lead_time_days' => 30, 'warranty_months' => 12]))->assertSessionHasNoErrors();
        $p = $product->fresh();
        $this->assertSame('approved', $p->vendor_status, 'extra details do not send an approved listing back for review');
        $this->assertSame(30, $p->lead_time_days);
    }

    public function test_the_forms_show_every_new_field_and_keep_typed_values_after_an_error(): void
    {
        $page = $this->actingAs($this->user)->get(route('vendor.products.create'))->assertOk();
        foreach (ProductExtraFields::fields() as $name => $field) {
            $page->assertSee('name="' . $name . '"', false);
        }
        $page->assertSee('name="mrp"', false)->assertSee('name="detail_group"', false)
            ->assertSee('Identity, material and finish')->assertSee('Warranty and returns')->assertSee('Packing and dispatch')
            ->assertSee('12. Search engine listing');
        $this->assertCount(25, ProductExtraFields::groupsForForm());

        // A failed save brings the typed values back, including the chosen kind of product and its details.
        $this->actingAs($this->user)->from(route('vendor.products.create'))->post(route('vendor.products.store'), $this->payload([
            'price' => '', 'manufacturer' => 'Acme Works', 'detail_group' => 'LIGHTING', 'attr' => ['LIGHTING' => ['lighting_type' => 'Pendant']],
        ]))->assertSessionHasErrors('price');
        $again = $this->actingAs($this->user)->get(route('vendor.products.create'))->assertOk();
        $again->assertSee('value="Acme Works"', false);
        $again->assertSee('<option value="LIGHTING" selected>', false);
        $again->assertSee('Pendant');

        $product = Product::create(['category_id' => Category::first()->id, 'vendor_id' => $this->user->vendorAccount()->id, 'vendor_status' => 'pending', 'name' => 'Lamp', 'slug' => 'lamp', 'description' => 'x', 'sku' => 'L1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
            'lead_time_days' => 9, 'category_attributes' => ['group' => 'LIGHTING', 'values' => ['wattage' => '12 W']]]);
        $this->actingAs($this->user)->get(route('vendor.products.edit', $product))->assertOk()
            ->assertSee('value="9"', false)->assertSee('12 W')->assertSee('<option value="LIGHTING" selected>', false);
    }

    public function test_the_category_details_match_the_vendor_sheet(): void
    {
        $groups = ProductExtraFields::groups();
        $this->assertCount(25, $groups);
        $this->assertSame(820, collect($groups)->sum(fn ($g) => count($g['fields'])));
        // Keys are unique inside a group, and the form never posts more than one group's worth of fields.
        foreach ($groups as $key => $group) {
            $keys = array_column($group['fields'], 'key');
            $this->assertSame(count($keys), count(array_unique($keys)), "$key has a repeated detail");
            $this->assertLessThanOrEqual(54, count($keys));
        }
        $this->assertContains('wood_species', array_column($groups['BED']['fields'], 'key'));
        $this->assertSame('bool', collect($groups['BED']['fields'])->firstWhere('key', 'headboard_included')['type']);
    }

    public function test_the_migration_is_safe_on_a_server_that_already_has_some_columns(): void
    {
        $migration = require base_path('database/migrations/2026_10_25_100000_add_vendor_sheet_fields_to_products.php');
        $migration->up();   // everything is already there: must not fail or duplicate
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('products', ['vendor_sku', 'lead_time_days', 'category_attributes', 'features', 'warranty_months']));
    }

    public function test_the_details_show_on_the_product_page_and_the_admin_page_but_private_ones_stay_private(): void
    {
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload([
            'material_grade' => 'Teak grade A', 'lead_time_days' => 21, 'return_policy' => 'Custom sizes are final sale', 'is_fragile' => 1, 'is_bulky' => 0,
            'vendor_sku' => 'SECRET-SKU', 'vendor_notes' => 'internal note', 'dispatch_from' => 'Jaipur',
            'detail_group' => 'BED', 'attr' => ['BED' => ['bed_style' => 'Platform', 'headboard_included' => 'Yes']],
        ]))->assertSessionHasNoErrors();
        $product = Product::first();
        $product->update(['is_active' => true, 'vendor_status' => 'approved']);
        auth()->logout();

        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('Teak grade A')->assertSee('Custom sizes are final sale')->assertSee('Platform')->assertSee('Headboard Included')
            ->assertSee('Fragile')->assertDontSee('Oversize / bulky')
            ->assertDontSee('SECRET-SKU')->assertDontSee('internal note')->assertDontSee('Jaipur');

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.products.show', $product))->assertOk()
            ->assertSee('Teak grade A')->assertSee('SECRET-SKU')->assertSee('internal note')->assertSee('Jaipur')->assertSee('Platform');
    }

    public function test_a_product_with_no_extra_details_shows_nothing_extra(): void
    {
        $product = Product::create(['category_id' => Category::firstOrCreate(['slug' => 'beds'], ['name' => 'Beds'])->id, 'vendor_id' => $this->user->vendorAccount()->id, 'vendor_status' => 'approved', 'name' => 'Plain', 'slug' => 'plain', 'description' => 'x', 'sku' => 'P1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true]);
        $this->assertSame([], ProductExtraFields::present($product, true));
        $this->get(route('products.show', $product))->assertOk();
    }

    public function test_blank_boxes_in_the_real_form_do_not_break_the_save(): void
    {
        // A browser posts every box, empty ones as "". Empty country must fall back to the database default, not NULL.
        $blank = collect(ProductExtraFields::fields())->map(fn ($f) => $f['type'] === 'bool' ? '0' : '')->all();
        $this->actingAs($this->user)->post(route('vendor.products.store'), $this->payload($blank + ['mrp' => '', 'detail_group' => '']))
            ->assertSessionHasNoErrors()->assertRedirect(route('vendor.products.index'));
        $this->assertSame(1, Product::count());
    }
}

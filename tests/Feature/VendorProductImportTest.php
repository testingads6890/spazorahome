<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Support\ProductExtraFields;
use App\Support\SimpleXlsx;
use App\Support\VendorProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorProductImportTest extends TestCase
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
        Category::create(['name' => 'Beds', 'slug' => 'beds', 'status' => true]);
    }

    private function row(array $o = []): array
    {
        return $o + ['category' => 'Beds', 'name' => 'Teak Bed', 'sku' => 'B-1', 'hsn_code' => '94035000', 'tax_rate' => '18', 'price' => '20000', 'stock_quantity' => '5', 'description' => 'Solid teak'];
    }

    /** Builds an uploaded .xlsx the way a seller would fill the template (headings from the template). */
    private function sheet(array $rows, string $ext = 'xlsx'): UploadedFile
    {
        $cols = VendorProductImport::columns();
        $matrix = [array_column($cols, 0)];
        foreach ($rows as $r) {
            $matrix[] = array_map(fn ($k) => (string) ($r[$k] ?? ''), array_keys($cols));
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.' . $ext;
        if ($ext === 'csv') {
            $h = fopen($path, 'w');
            foreach ($matrix as $line) {
                fputcsv($h, $line, ',', '"', '\\');
            }
            fclose($h);
        } else {
            SimpleXlsx::write($path, ['Products' => $matrix]);
        }

        return new UploadedFile($path, 'products.' . $ext, null, null, true);
    }

    private function upload(array $rows, string $ext = 'xlsx')
    {
        return $this->actingAs($this->user)->post(route('vendor.products.import.store'), ['file' => $this->sheet($rows, $ext)]);
    }

    public function test_heading_names_are_unique_so_columns_never_clash(): void
    {
        $heads = array_map(fn ($c) => preg_replace('/[^a-z0-9]/', '', strtolower($c[0])), VendorProductImport::columns());
        $this->assertSame(count($heads), count(array_unique($heads)));
    }

    public function test_the_template_downloads_and_can_be_read_back(): void
    {
        $res = $this->actingAs($this->user)->get(route('vendor.products.import.template'))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        file_put_contents($path, $res->streamedContent());
        $rows = SimpleXlsx::read($path);
        $this->assertSame(array_column(VendorProductImport::columns(), 0), $rows[0]);
        $this->assertCount(1, $rows, 'the Products sheet has only the heading row');
        $zip = new \ZipArchive();
        $zip->open($path);
        $this->assertStringContainsString('Beds', $zip->getFromName('xl/worksheets/sheet3.xml'));
        $this->assertStringContainsString('WOOD', strtoupper($zip->getFromName('xl/worksheets/sheet4.xml')));
        $zip->close();
    }

    public function test_good_rows_become_pending_products_with_every_detail(): void
    {
        $this->upload([$this->row([
            'brand' => 'Acme', 'tags' => 'teak, bed, Teak', 'mrp' => '30000', 'lead_time_days' => '14', 'made_to_order' => 'Yes', 'return_eligible' => 'No',
            'unit' => 'Set', 'assembly_difficulty' => 'Medium', 'packed_width_cm' => '180.5', 'is_fragile' => 'yes', 'price_valid_until' => now()->addMonth()->format('Y-m-d'),
            'payment_terms' => '50% advance', 'faq_1_question' => 'Is it solid wood?', 'faq_1_answer' => 'Yes, teak.', 'whats_included' => "Bed\nSlats",
            'detail_group' => 'BED', 'details' => 'Bed Style=Platform; Headboard Included=yes',
        ])])->assertSessionHasNoErrors()->assertSessionHas('import_result');

        $p = Product::first();
        $this->assertSame('pending', $p->vendor_status);
        $this->assertSame($this->user->vendorAccount()->id, $p->vendor_id);
        $this->assertTrue($p->is_active);
        $this->assertSame(['teak', 'bed'], $p->tags);
        $this->assertSame('set', $p->unit);
        $this->assertTrue($p->made_to_order);
        $this->assertFalse($p->return_eligible);
        $this->assertTrue($p->is_fragile);
        $this->assertSame(14, $p->lead_time_days);
        $this->assertSame('30000.00', $p->mrp);
        $this->assertSame('Yes, teak.', $p->faq_1_answer);
        $this->assertSame('Medium', $p->assembly_difficulty);
        $this->assertSame(['group' => 'BED', 'values' => ['bed_style' => 'Platform', 'headboard_included' => 'Yes']], $p->category_attributes);
        $this->assertSame(1, VendorProduct::where('product_id', $p->id)->where('status', 'pending')->count());
        $this->assertSame('teak-bed', $p->slug);
    }

    public function test_bad_rows_are_reported_with_their_excel_row_number_and_good_rows_still_go_in(): void
    {
        $res = $this->upload([
            $this->row(['sku' => 'OK-1', 'name' => 'Good one']),                                   // row 2
            $this->row(['sku' => 'BAD-1', 'name' => 'Bad GST', 'tax_rate' => '7', 'price' => 'abc']),   // row 3
            $this->row(['sku' => 'BAD-2', 'name' => 'Bad category', 'category' => 'Spaceships']),        // row 4
            $this->row(['sku' => 'BAD-3', 'name' => 'Bad details', 'detail_group' => 'BED', 'details' => 'Nope=1; Headboard Included=Maybe', 'mrp' => '5']), // row 5
            $this->row(['sku' => 'OK-1', 'name' => 'Same sku again']),                              // row 6: skipped? no, first is created in the same file
        ]);
        $r = $res->assertSessionHas('import_result')->getSession()->get('import_result');

        $this->assertSame(5, $r["total"]);
        $this->assertSame(1, $r['created']);
        $this->assertSame(['OK-1'], Product::pluck('sku')->all());
        $byRow = collect($r['errors'])->keyBy('row');
        $this->assertSame([3, 4, 5], $byRow->keys()->take(3)->all());
        $this->assertStringContainsString('gst', strtolower(implode(' ', $byRow[3]['messages'])));
        $this->assertStringContainsString('price', strtolower(implode(' ', $byRow[3]['messages'])));
        $this->assertStringContainsString('Spaceships', implode(' ', $byRow[4]['messages']));
        $this->assertStringContainsString('Nope', implode(' ', $byRow[5]['messages']));
        $this->assertStringContainsString('Yes or No', implode(' ', $byRow[5]['messages']));
        $this->assertStringContainsString('MRP', implode(' ', $byRow[5]['messages']));
        $this->assertSame(1, count($r['skipped']) + ($byRow->has(6) ? 1 : 0), 'a repeat of a SKU is reported, not added twice');
        $this->assertSame(1, Product::count());
    }

    public function test_uploading_the_same_file_twice_is_safe(): void
    {
        $this->upload([$this->row()]);
        $r = $this->upload([$this->row()])->getSession()->get('import_result');
        $this->assertSame(0, $r['created']);
        $this->assertCount(1, $r['skipped']);
        $this->assertSame(1, Product::count());
    }

    public function test_another_sellers_sku_is_refused(): void
    {
        $other = User::factory()->create();
        $v = Vendor::create(['user_id' => $other->id, 'business_name' => 'Other', 'slug' => 'other', 'email' => 'o@e.com', 'status' => 'approved', 'is_active' => true]);
        Product::create(['category_id' => Category::first()->id, 'vendor_id' => $v->id, 'vendor_status' => 'approved', 'name' => 'X', 'slug' => 'x', 'description' => 'x', 'sku' => 'B-1', 'price' => 1, 'stock_quantity' => 1, 'is_active' => true]);
        $r = $this->upload([$this->row()])->getSession()->get('import_result');
        $this->assertSame(0, $r['created']);
        $this->assertCount(1, $r['errors']);
    }

    public function test_a_csv_works_and_excel_number_cells_are_read_cleanly(): void
    {
        $r = $this->upload([$this->row(['sku' => 'C-1', 'hsn_code' => '94035000', 'tax_rate' => '18%', 'price' => '1999.50'])], 'csv')->getSession()->get('import_result');
        $this->assertSame(1, $r['created']);
        $this->assertSame('18.00', (string) Product::first()->tax_rate === '18' ? '18.00' : (string) Product::first()->tax_rate);
        $this->assertSame('94035000', Product::first()->hsn_code);
    }

    public function test_a_file_without_the_right_headings_or_a_wrong_file_type_is_refused(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        SimpleXlsx::write($path, ['Sheet1' => [['Foo', 'Bar'], ['1', '2']]]);
        $this->actingAs($this->user)->post(route('vendor.products.import.store'), ['file' => new UploadedFile($path, 'x.xlsx', null, null, true)])->assertSessionHasErrors('file');

        $bad = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        file_put_contents($bad, 'not a zip');
        $this->actingAs($this->user)->post(route('vendor.products.import.store'), ['file' => new UploadedFile($bad, 'x.xlsx', null, null, true)])->assertSessionHasErrors('file');

        $this->actingAs($this->user)->post(route('vendor.products.import.store'), ['file' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('file');
        $this->assertSame(0, Product::count());
    }

    public function test_the_page_renders_and_only_sellers_with_product_access_can_use_it(): void
    {
        $this->actingAs($this->user)->get(route('vendor.products.import'))->assertOk()->assertSee('Download Excel template');
        $this->actingAs($this->user)->get(route('vendor.products.index'))->assertOk()->assertSee('Import from Excel');
        auth()->logout();
        $this->get(route('vendor.products.import'))->assertRedirect();
        $customer = User::factory()->create();
        $this->actingAs($customer)->post(route('vendor.products.import.store'), [])->assertForbidden();
    }
}

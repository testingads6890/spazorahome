<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Vendors manage only their own products. A new listing waits for admin approval; a listing the admin
 * rejected or sent back for changes goes into the approval queue again as soon as the vendor saves it.
 */
class ProductController extends Controller
{
    public const MAX_IMAGES = 8;

    public function index(Request $request)
    {
        $vendorId = $this->vendor()->id;
        $base = fn () => Product::where('vendor_id', $vendorId);

        $counts = [
            'all' => $base()->count(),
            'pending' => $base()->where('vendor_status', 'pending')->count(),
            'approved' => $base()->where('vendor_status', 'approved')->count(),
            'changes_requested' => $base()->where('vendor_status', 'changes_requested')->count(),
            'rejected' => $base()->where('vendor_status', 'rejected')->count(),
            'low_stock' => $base()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
        ];

        $tab = array_key_exists((string) $request->query('status'), $counts) ? (string) $request->query('status') : 'all';
        $query = $base()->with(['images', 'category:id,name']);
        if ($tab === 'low_stock') {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        } elseif ($tab !== 'all') {
            $query->where('vendor_status', $tab);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        return view('vendor.products.index', compact('products', 'counts', 'tab'));
    }

    public function create()
    {
        return view('vendor.products.create', ['categories' => $this->categories(), 'product' => new Product(['min_order_qty' => 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $vendor = $this->vendor();

        DB::transaction(function () use ($data, $request, $vendor) {
            $product = Product::create($data + [
                'vendor_id' => $vendor->id,
                'vendor_status' => 'pending',
                'slug' => $this->uniqueSlug($data['name']),
                'is_active' => true,
                'is_featured' => false,
            ]);

            \App\Support\ProductImages::save($request, $product);
            \App\Support\DeliveryArea::syncPincodes($product, $request);
        \App\Support\CategoryPicker::sync($product, $request);

            VendorProduct::create(['vendor_id' => $vendor->id, 'product_id' => $product->id, 'status' => 'pending']);
        });

        return redirect()->route('vendor.products.index')->with('success', 'Product submitted for approval.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);
        $product->load('images');

        return view('vendor.products.edit', ['product' => $product, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = $this->validated($request, $product);
        // Rejected or sent back for changes: saving it puts it in front of the admin again.
        $resubmit = in_array($product->vendor_status, ['rejected', 'changes_requested'], true);

        DB::transaction(function () use ($data, $request, $product, $resubmit) {
            // SKU and tax rate were reviewed at approval, so an approved listing keeps them. is_featured and
            // vendor_status are admin-controlled and never come from this form.
            // The GST rate is fixed once a product is listed (invoices depend on it); SKU and HSN are fixed after approval.
            unset($data['tax_rate']);
            if ($product->vendor_status === 'approved') {
                unset($data['sku'], $data['hsn_code']);
            }
            $product->update($data + ($resubmit ? ['vendor_status' => 'pending', 'vendor_rejection_reason' => null] : []));

            \App\Support\ProductImages::save($request, $product);
            \App\Support\DeliveryArea::syncPincodes($product, $request);
        \App\Support\CategoryPicker::sync($product, $request);

            if ($resubmit) {
                VendorProduct::updateOrCreate(
                    ['vendor_id' => $product->vendor_id, 'product_id' => $product->id],
                    ['status' => 'pending', 'rejection_reason' => null]
                );
            }
        });

        return redirect()->route('vendor.products.index')
            ->with('success', $resubmit ? 'Product updated and sent for approval again.' : 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);
        $product->delete();

        return redirect()->route('vendor.products.index')->with('success', 'Product removed.');
    }

    /** One set of rules for adding and editing, so the two forms can never drift apart. */
    private function validated(Request $request, ?Product $product = null): array
    {

        $rules = \App\Support\VendorProductRules::base($product) + \App\Support\ProductImages::rules() + \App\Support\BulkPricing::rules() + \App\Support\ProductExtraFields::rules();
        // Editing: fields the form did not send keep their saved value (quick edits from lists, older forms).
        if ($product) {
            foreach (['category_id', 'sku', 'tax_rate', 'hsn_code', 'description'] as $field) {
                if (! $request->has($field)) {
                    $rules[$field] = array_merge(['sometimes'], (array) $rules[$field]);
                }
            }
        }
        // A typed category replaces the list choice, so the list becomes optional when one is typed.
        if (filled($request->input('custom_category'))) {
            $rules['category_id'] = ['nullable', \Illuminate\Validation\Rule::exists('categories', 'id')];
        }
        $rules = array_merge($rules, \App\Support\ProductFormData::rules(false, $request));
        // Quick edits from lists do not send the location: keep the saved one rather than demanding it again.
        if ($product) {
            foreach (['country_id', 'state_id', 'district_id', 'city_id', 'pincode_id'] as $f) {
                if (! $request->has($f)) {
                    $rules[$f] = array_merge(['sometimes'], (array) $rules[$f]);
                }
            }
        }
        $data = $request->validate($rules, \App\Support\ProductImages::messages() + \App\Support\ProductExtraFields::messages() + \App\Support\VendorProductRules::MESSAGES + \App\Support\ProductFormData::messages());
        // Typed category: a new one awaits our approval; colours, materials and the rest become single stored values.
        $data = \App\Support\ProductFormData::prepare($data, false);

        // "oak, hand made , Oak" => ["oak", "hand made"]
        // On an edit, anything the form did not send keeps its saved value.
        $sent = fn (string $field) => $product === null || $request->has($field);
        if ($sent('tags')) {
            $data['tags'] = collect(explode(',', (string) ($data['tags'] ?? '')))->map(fn ($t) => trim($t))->filter()
                ->unique(fn ($t) => mb_strtolower($t))->take(15)->values()->all() ?: null;
        }
        if ($sent('min_order_qty')) {
            $data['min_order_qty'] = (int) ($data['min_order_qty'] ?? 1) ?: 1;
        }
        if ($sent('low_stock_threshold')) {
            $data['low_stock_threshold'] = (int) ($data['low_stock_threshold'] ?? 5);
        }
        if ($sent('bulk_mode')) {
            $data['bulk_pricing'] = \App\Support\BulkPricing::fromRequest($request);
        }
        // Details for one kind of product (a bed's slats, a lamp's lumens...): the chosen group replaces what was saved.
        if ($sent('detail_group')) {
            $data['category_attributes'] = \App\Support\ProductExtraFields::categoryAttributes($request);
        }

        \App\Support\ProductImages::check($request, $product);

        // A blank country keeps the database default (India) instead of trying to store NULL.
        if (($data['country_of_origin'] ?? null) === null) {
            unset($data['country_of_origin']);
        }

        return collect($data)->except(['images', 'remove_images', 'thumbnail', 'primary_image', 'bulk_mode', 'bulk_min', 'bulk_off', 'detail_group', 'attr'])->all();
    }

    /** Active categories with their subcategories underneath, for the grouped picker. */
    private function categories()
    {
        $all = Category::where('status', true)->orderBy('name')->get(['id', 'name', 'parent_id']);

        return $all->whereNull('parent_id')->values()->map(function (Category $parent) use ($all) {
            $parent->setRelation('children', $all->where('parent_id', $parent->id)->values());

            return $parent;
        });
    }

    private function vendor()
    {
        return auth()->user()->vendorAccount();
    }

    private function authorizeOwner(Product $product): void
    {
        abort_unless($product->vendor_id === $this->vendor()->id, 403);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        for ($i = 2; Product::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}

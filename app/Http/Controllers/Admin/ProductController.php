<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\Admin\ProductRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $products = $query->latest()->paginate(20);
        $categories = Category::all();
        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        $product = new Product(['is_active' => true, 'low_stock_threshold' => 5]);
        return view('admin.products.create', compact('categories', 'product'));
    }

    public function store(ProductRequest $request)
    {
        $this->validatePictures($request);
        $product = Product::create($this->withDefaults(\App\Support\ProductFormData::prepare($request->validated(), true)) + $this->bulkPricing($request));
        \App\Support\DeliveryArea::syncPincodes($product, $request);
        \App\Support\CategoryPicker::sync($product, $request);
        \App\Support\ProductImages::save($request, $product);
        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load('images');
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->validatePictures($request, $product);
        $product->update($this->withDefaults(\App\Support\ProductFormData::prepare($request->validated(), true)) + $this->bulkPricing($request));
        \App\Support\DeliveryArea::syncPincodes($product, $request);
        \App\Support\CategoryPicker::sync($product, $request);
        \App\Support\ProductImages::save($request, $product);
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    /** Blank optional numeric inputs arrive as null; fall back to column defaults instead of violating NOT NULL. */
    private function withDefaults(array $data): array
    {
        $data['low_stock_threshold'] = $data['low_stock_threshold'] ?? 5;
        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['hsn_code'] = ($data['hsn_code'] ?? '') === '' ? null : $data['hsn_code'];
        $data['tax_rate'] = ($data['tax_rate'] ?? '') === '' ? null : $data['tax_rate'];

        return $data;
    }

    /** Bulk (quantity) pricing from the form, only when the form has those fields. */
    private function bulkPricing(\Illuminate\Http\Request $request): array
    {
        if (! $request->has('bulk_mode')) {
            return [];
        }
        $request->validate(\App\Support\BulkPricing::rules());

        return ['bulk_pricing' => \App\Support\BulkPricing::fromRequest($request)];
    }

    /** Thumbnail and gallery pictures (the same rules as the seller form). */
    private function validatePictures(Request $request, ?Product $product = null): void
    {
        $request->validate(\App\Support\ProductImages::rules(), \App\Support\ProductImages::messages());
        \App\Support\ProductImages::check($request, $product);
    }
}

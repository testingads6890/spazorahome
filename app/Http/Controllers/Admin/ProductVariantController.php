<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        $variants = $product->variants()->paginate(20);
        return view('admin.products.variants.index', compact('product', 'variants'));
    }

    public function create(Product $product)
    {
        return view('admin.products.variants.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'sku' => 'required|string|max:255|unique:product_variants',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'attributes' => 'nullable|string|max:2000',
        ]);

        $product->variants()->create(array_merge($request->only(['sku', 'price', 'sale_price', 'stock_quantity']), ['attributes' => $this->attributes($request)]));

        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant created successfully.');
    }

    public function edit(Product $product, ProductVariant $variant)
    {
        return view('admin.products.variants.edit', compact('product', 'variant'));
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        $request->validate([
            'sku' => 'required|string|max:255|unique:product_variants,sku,' . $variant->id,
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'attributes' => 'nullable|string|max:2000',
        ]);
        abort_unless($variant->product_id === $product->id, 404);

        $variant->update(array_merge($request->only(['sku', 'price', 'sale_price', 'stock_quantity']), ['attributes' => $this->attributes($request)]));

        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant updated successfully.');
    }

    /**
     * The attributes box takes one "Name: value" per line (Colour: Red), or the older JSON form {"colour": "Red"}.
     * These values make up the variant's label in the shop, e.g. "Red / Large".
     */
    private function attributes(Request $request): ?array
    {
        $text = trim((string) $request->input('attributes'));
        if ($text === '') {
            return null;
        }

        $fail = fn () => throw \Illuminate\Validation\ValidationException::withMessages([
            'attributes' => 'Write one attribute per line as "Name: value", for example "Colour: Red".',
        ]);

        if (str_starts_with($text, '{')) {
            $json = json_decode($text, true);
            if (! is_array($json)) {
                $fail();
            }
            $pairs = $json;
        } else {
            $pairs = [];
            foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $parts = array_map('trim', explode(':', $line, 2));
                if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                    $fail();
                }
                $pairs[$parts[0]] = $parts[1];
            }
        }

        foreach ($pairs as $name => $value) {
            if (! is_string($name) || ! is_scalar($value) || mb_strlen($name) > 60 || mb_strlen((string) $value) > 120) {
                $fail();
            }
            $pairs[$name] = (string) $value;
        }

        return $pairs ?: null;
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $variant->delete();
        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant deleted successfully.');
    }
}
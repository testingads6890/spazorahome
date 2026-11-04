<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Validation\Rule;

/** The rules for the main product details: one copy, used by the seller form and by the Excel import. */
class VendorProductRules
{
    public const MESSAGES = [
        'hsn_code.regex' => 'The HSN code is 4, 6 or 8 digits.',
        'sale_price.lte' => 'The sale price cannot be higher than the price.',
    ];

    public static function base(?Product $product = null): array
    {
        return [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('status', true)],
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'brand' => 'nullable|string|max:100',
            'product_type' => 'nullable|string|max:100',
            'short_description' => 'nullable|string|max:3000',
            'description' => 'required|string|max:20000',
            'specifications' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0|max:99999999',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'tax_rate' => ['required', 'numeric', Rule::in(config('shop.gst_slabs'))],
            'hsn_code' => ['required', 'regex:/^\d{4}(\d{2}(\d{2})?)?$/'],
            'stock_quantity' => 'required|integer|min:0|max:1000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
            'min_order_qty' => 'nullable|integer|min:1|max:100000',
            'material' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'finish' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:100',
            'dimensions' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:50',
            'customization_info' => 'nullable|string|max:2000',
            'shipping_info' => 'nullable|string|max:2000',
            'warranty' => 'nullable|string|max:2000',
            'care_instructions' => 'nullable|string|max:2000',
            'tags' => 'nullable|string|max:500',
            'video_url' => 'nullable|url|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

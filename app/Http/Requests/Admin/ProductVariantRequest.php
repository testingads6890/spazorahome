<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:100|unique:product_variants,sku,' . $this->route('variant')?->id,
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'stock_quantity' => 'required|integer|min:0',
            'attributes' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'Variant SKU is required.',
            'sku.unique' => 'This SKU already exists.',
        ];
    }
}

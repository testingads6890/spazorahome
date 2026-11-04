<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The blocked-countries box arrives empty or as a list; blank export numbers fall back to their defaults. */
    protected function prepareForValidation(): void
    {
        if ($this->has('export_blocked_countries')) {
            $this->merge(['export_blocked_countries' => array_values(array_filter((array) $this->input('export_blocked_countries')))]);
        }
        if ($this->has('export_min_qty') && blank($this->input('export_min_qty'))) {
            $this->merge(['export_min_qty' => 1]);
        }
        if ($this->has('country_of_origin') && blank($this->input('country_of_origin'))) {
            $this->merge(['country_of_origin' => \App\Support\Countries::INDIA]);
        }
    }

    public function rules(): array
    {
        return array_merge([
            'category_id' => 'nullable|required_without:custom_category|exists:categories,id',
            'custom_category' => 'nullable|required_without:category_id|string|max:120',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $this->product?->id,
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:3000',
            'sku' => 'required|string|max:100|unique:products,sku,' . $this->product?->id,
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'is_featured' => 'sometimes|boolean',
            'export_available' => 'sometimes|boolean',
            'export_weight_kg' => 'nullable|numeric|min:0|max:99999',
            'country_of_origin' => 'sometimes|string|max:100',
            'export_min_qty' => 'sometimes|integer|min:1|max:100000',
            'export_blocked_countries' => 'sometimes|array',
            'export_blocked_countries.*' => [\Illuminate\Validation\Rule::in(\App\Support\Countries::all())],
            'user_role' => 'nullable|in:customer,interior,architecture,designer,other',
            'hsn_code' => ['nullable', 'regex:/^\d{4}(\d{2}(\d{2})?)?$/'],
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'brand' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'style' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ], \App\Support\ProductExtraFields::rules(), \App\Support\ProductFormData::rules(true, $this));
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'SKU is required.',
            'hsn_code.regex' => 'HSN code must be 4, 6 or 8 digits.',
            'sku.unique' => 'This SKU already exists.',
            'price.required' => 'Price is required.',
            'sale_price.lte' => 'Sale price cannot exceed regular price.',
        ] + \App\Support\ProductFormData::messages();
    }
}

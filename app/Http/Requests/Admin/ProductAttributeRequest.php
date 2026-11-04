<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:product_attributes,slug,' . $this->route('product_attribute')?->id,
            'type' => 'required|in:text,select,color',
            'options' => 'nullable|array',
            'is_required' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Attribute name is required.',
            'type.required' => 'Attribute type is required.',
            'type.in' => 'Invalid attribute type.',
        ];
    }
}

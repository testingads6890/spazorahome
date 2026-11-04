<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255|required_without:image_file',
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|required_without:image',
            'link' => ['nullable', 'string', 'max:255', new \App\Rules\LinkOrPath],
            'position' => 'required|in:homepage,category',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Banner title is required.',
            'position.required' => 'Banner position is required.',
        ];
    }
}

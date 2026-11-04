<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** With exports on, the country comes from a list; the usual spellings of India are tidied to one. */
    protected function prepareForValidation(): void
    {
        if (config('shop.export.enabled') && \App\Support\Countries::isIndia($this->input('country'))) {
            $this->merge(['country' => \App\Support\Countries::INDIA]);
        }
    }

    public function rules(): array
    {
        $export = (bool) config('shop.export.enabled');
        $foreign = $export && ! \App\Support\Countries::isIndia($this->input('country'));

        return [
            'type' => 'required|in:billing,shipping',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            // An Indian address needs a GST state; an address abroad takes its state or region as typed.
            'state' => $foreign ? 'required|string|max:100' : ['required', \Illuminate\Validation\Rule::in(\App\Support\GstState::names())],
            'postal_code' => 'required|string|max:20',
            'country' => $export ? ['required', \Illuminate\Validation\Rule::in(\App\Support\Countries::all())] : 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'address_line_1.required' => 'Address line 1 is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'postal_code.required' => 'Postal code is required.',
            'country.required' => 'Country is required.',
        ];
    }
}

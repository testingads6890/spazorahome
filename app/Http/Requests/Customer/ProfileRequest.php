<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => 'nullable|string|max:20',
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'other_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.max' => 'Phone number is too long.',
            'date_of_birth.before' => 'Date of birth must be a past date.',
            'gender.in' => 'Invalid gender selected.',
        ];
    }
}

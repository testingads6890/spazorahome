<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RoomIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:room_ideas,slug,' . $this->route('room_idea')?->id,
            'description' => 'required|string',
            'image' => 'required|string|max:255',
            'room_type' => 'required|in:living_room,bedroom,kitchen,bathroom,office',
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
            'room_type.required' => 'Room type is required.',
            'room_type.in' => 'Invalid room type selected.',
        ];
    }
}

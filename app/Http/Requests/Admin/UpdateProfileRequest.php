<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the authenticated admin can update their own profile
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[\d\s\-()]{7,15}$/', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.min'      => 'Name must be at least 2 characters.',
            'name.max'      => 'Name must not exceed 100 characters.',
            'phone.regex'   => 'Please enter a valid phone number.',
        ];
    }
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_email'        => [
                'required',
                'email',
                'max:255',
                // Unique across users table, excluding current admin
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
            'new_email.required'        => 'New email address is required.',
            'new_email.email'           => 'Please enter a valid email address.',
            'new_email.unique'          => 'This email address is already in use.',
        ];
    }
}
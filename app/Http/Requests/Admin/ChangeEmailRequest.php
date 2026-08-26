<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_email' => [
                'required',
                'email',
            ],
            'new_email' => [
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
            'current_email.required' => 'Current email is required.',
            'current_email.email'    => 'Please enter a valid current email address.',
            'new_email.required'     => 'New email address is required.',
            'new_email.email'        => 'Please enter a valid email address.',
            'new_email.unique'       => 'This email address is already in use.',
        ];
    }

    /**
     * Extra check: current_email must actually match the logged-in user's email.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('current_email') && $this->user()) {
                if ($this->input('current_email') !== $this->user()->email) {
                    $validator->errors()->add('current_email', 'Current email does not match our records.');
                }
            }
        });
    }
}
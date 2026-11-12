<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

final class PasswordUpdateRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                PasswordRule::min(8),
                'confirmed',
            ],
            'password_confirmation' => ['required'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'current_password' => __('users.fields.current_password'),
            'password' => __('users.fields.new_password'),
            'password_confirmation' => __('users.fields.password_confirmation'),
        ];
    }
}

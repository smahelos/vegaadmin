<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profile update of the signed-in user. The password changes only through
 * PasswordUpdateRequest, which asks for the current password.
 */
final class UserRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $emailUnique = Rule::unique('users', 'email');
        $emailUnique->ignore($user?->id);

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                $emailUnique,
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
        ];
    }
}

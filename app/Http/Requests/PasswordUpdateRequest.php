<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            // Use Laravel Password rule (keeps min 8; can be extended later for complexity)
            'password' => ['required', PasswordRule::min(8), 'confirmed'],
            'password_confirmation' => ['required'],
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'current_password' => __('users.fields.current_password'),
            'password' => __('users.fields.new_password'),
            'password_confirmation' => __('users.fields.password_confirmation'),
        ];
    }

    /**
     * Get custom error messages for validation rules
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'current_password.required' => __('users.validation.password_required'), // Reuse generic password required text
            'current_password.current_password' => __('users.messages.profile_error_update_password_current'),
            'password.required' => __('users.validation.password_required'),
            'password.min' => __('users.validation.password_min', ['min' => 8]),
            'password.confirmed' => __('users.validation.password_confirmed'),
            'password_confirmation.required' => __('users.validation.password_confirmation_required'),
        ];
    }
}

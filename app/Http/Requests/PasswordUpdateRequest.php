<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
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
            'current_password.required' => __('users.validation.current_password_required'),
            'current_password.current_password_invalid' => __('users.validation.current_password_invalid'),
            'current_password.min' => __('users.validation.current_password', ['min' => 8]),
            'current_password.current_password' => __('users.validation.current_password_invalid'),
            'password.required' => __('users.validation.password_required'),
            'password.min' => __('users.validation.password_min', ['min' => 8]),
            'password.confirmed' => __('users.validation.password_confirmed'),
            'password_confirmation.required' => __('users.validation.password_confirmation_required'),
        ];
    }
}
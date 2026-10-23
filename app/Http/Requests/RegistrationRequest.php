<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'description' => ['nullable', 'string', 'max:1000'],

            // Bank account details
            'account_number' => ['nullable', 'string', 'max:50'],
            'bank_code' => ['nullable', 'required_with:account_number', 'string', 'max:10'],
            'iban' => ['nullable', 'string', 'max:50'],
            'swift' => ['nullable', 'required_with:iban', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Registration creates the user and their default supplier, so address,
     * company and bank fields are named like the supplier's.
     */
    public function attributes(): array
    {
        $attributes = [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
            'password' => __('users.fields.password'),
            'password_confirmation' => __('users.fields.password_confirmation'),
        ];
        foreach (['street', 'city', 'zip', 'country', 'phone', 'ico', 'dic', 'description', 'account_number', 'bank_code', 'iban', 'swift', 'bank_name'] as $field) {
            $attributes[$field] = __("suppliers.fields.$field");
        }

        return $attributes;
    }
}

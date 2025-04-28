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
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|integer',
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
     * Get custom error messages for validation rules
     *
     * @return array
     */
    public function messages()
    {
        return [
            'password.required' => __('users.validation.password_required'),
            'street.required' => __('users.validation.street_required'),
            'city.required' => __('users.validation.city_required'),
            'zip.required' => __('users.validation.zip_required'),
            'country.required' => __('users.validation.country_required'),
            'name.required' => __('users.validation.name_required'),
            'email.required' => __('users.validation.email_required'),
            'email.email' => __('users.validation.email_email'),
            'email.unique' => __('users.validation.email_unique'),
            'password.min' => __('users.validation.password_min'),
            'password.confirmed' => __('users.validation.password_confirmed'),
            
            // Bank account validation messages
            'account_number.max' => __('suppliers.validation.account_number_format'),
            'bank_code.required_with' => __('suppliers.validation.bank_code_required'),
            'iban.max' => __('suppliers.validation.iban_format'),
            'swift.required_with' => __('suppliers.validation.swift_required'),
        ];
    }
}
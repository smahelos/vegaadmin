<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'shortcut' => 'nullable|string|max:50',
            'phone' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            
            // Bank account details
            'account_number' => 'nullable|string|max:50',
            'bank_code' => 'nullable|required_with:account_number|string|max:10',
            'iban' => 'nullable|string|max:50',
            'swift' => 'nullable|required_with:iban|string|max:20',
            'bank_name' => 'nullable|string|max:255',
        ];

        // Email is always required
        $rules['email'] = [
            'required',
            'email',
        ];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('suppliers.fields.name'),
            'email' => __('suppliers.fields.email'),
            'user_id' => __('suppliers.fields.user_id'),
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
            'name.required' => __('suppliers.validation.name_required'),
            'email.required' => __('suppliers.validation.email_required'),
            'street.required' => __('suppliers.validation.street_required'),
            'city.required' => __('suppliers.validation.city_required'),
            'zip.required' => __('suppliers.validation.zip_required'),
            'country.required' => __('suppliers.validation.country_required'),
            'user_id.required' => __('suppliers.validation.user_required'),

            // Bank account validation messages
            'account_number.max' => __('suppliers.validation.account_number_format'),
            'bank_code.required_with' => __('suppliers.validation.bank_code_required'),
            'iban.max' => __('suppliers.validation.iban_format'),
            'swift.required_with' => __('suppliers.validation.swift_required'),
        ];
    }
}

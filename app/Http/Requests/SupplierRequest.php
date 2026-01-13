<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SupplierRequest extends BaseEntityRequest
{
    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'supplier';
    }

    /**
     * Get required permission for supplier operations
     */
    protected function getRequiredPermission(): string
    {
        return 'frontend.can_create_edit_supplier';
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        $maxFileSize = Config::get('file_upload.contexts.supplier_logo.max_kb', 2048);
        $rules = [
            'supplier_id' => 'nullable|integer',
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
            'supplier_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:' . $maxFileSize,
            'is_default' => 'nullable|boolean',

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
            'phone' => __('suppliers.fields.phone'),
            'street' => __('suppliers.fields.street'),
            'city' => __('suppliers.fields.city'),
            'zip' => __('suppliers.fields.zip'),
            'country' => __('suppliers.fields.country'),
            'ico' => __('suppliers.fields.ico'),
            'dic' => __('suppliers.fields.dic'),
            'shortcut' => __('suppliers.fields.shortcut'),
            'description' => __('suppliers.fields.description'),
            'supplier_logo' => __('suppliers.fields.supplier_logo'),
            'is_default' => __('suppliers.fields.is_default'),
            'account_number' => __('suppliers.fields.account_number'),
            'bank_code' => __('suppliers.fields.bank_code'),
            'iban' => __('suppliers.fields.iban'),
            'swift' => __('suppliers.fields.swift'),
            'bank_name' => __('suppliers.fields.bank_name'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator,
            redirect()->back()
                ->withErrors($validator->errors())
                ->withInput()
                ->with('error', trans('suppliers.messages.validation_failed'))
        );
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
            'email.email' => __('suppliers.validation.email_valid'),
            'phone.required' => __('suppliers.validation.phone_required'),
            'street.required' => __('suppliers.validation.street_required'),
            'city.required' => __('suppliers.validation.city_required'),
            'zip.required' => __('suppliers.validation.zip_required'),
            'country.required' => __('suppliers.validation.country_required'),
            'ico.max' => __('suppliers.validation.ico_format'),
            'supplier_logo.file' => __('suppliers.validation.supplier_logo_file'),
            'supplier_logo.image' => __('suppliers.validation.supplier_logo_format'),
            'supplier_logo.mimes' => __('suppliers.validation.supplier_logo_format'),
            'supplier_logo.max' => __('suppliers.validation.supplier_logo_size'),

            // Bank account validation messages
            'account_number.max' => __('suppliers.validation.account_number_format'),
            'bank_code.required_with' => __('suppliers.validation.bank_code_required'),
            'iban.max' => __('suppliers.validation.iban_format'),
            'swift.required_with' => __('suppliers.validation.swift_required'),
        ];
    }
}

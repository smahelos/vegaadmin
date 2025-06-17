<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow both authenticated and guest users
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'invoice_vs' => 'required|string|max:50',
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_currency' => 'required|string|max:3',
            'issue_date' => 'required|date',
            'tax_point_date' => 'nullable|date',
            'due_in' => 'required|integer|min:1',
            'payment_status_id' => 'required|exists:statuses,id',
            
            // Issuer details
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id', 'required_without:name'],
            'name' => ['nullable', 'string', 'min:3', 'max:255', 'required_without:supplier_id'],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'street' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'city' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'zip' => ['nullable', 'string', 'max:20', 'required_without:supplier_id'],
            'country' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'ico' => 'nullable|string|max:50',
            'dic' => 'nullable|string|max:50',
            'supplier_shortcut' => 'nullable|string|max:50',
            
            // Bank account details
            'account_number' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:10',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'swift' => 'nullable|string|max:11',
            
            // Client details
            'client_id' => ['nullable', 'integer', 'exists:clients,id', 'required_without:client_name'],
            'client_name' => ['nullable', 'string', 'min:3', 'max:255', 'required_without:client_id'],
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_street' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_city' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_zip' => ['nullable', 'string', 'max:20', 'required_without:client_id'],
            'client_country' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_ico' => 'nullable|string|max:50',
            'client_dic' => 'nullable|string|max:50',
            'client_shortcut' => 'nullable|string|max:50',
            
            // Invoice text
            'invoice_text' => 'nullable|string',
        ];

        return $rules;
    }

    /**
     * Get the validation attributes for error messages
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
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
            // Invoice basic details
            'invoice_vs.required' => __('invoices.validation.invoice_vs_required'),
            'due_in.required' => __('invoices.validation.due_in_required'),
            'payment_method_id.required' => __('invoices.validation.payment_method_required'),
            'payment_amount.required' => __('invoices.validation.amount_required'),
            'payment_amount.numeric' => __('invoices.validation.amount_numeric'),
            'payment_amount.min' => __('invoices.validation.amount_min'),
            'payment_currency.required' => __('invoices.validation.currency_required'),
            'issue_date.required' => __('invoices.validation.issue_date_required'),
            'payment_status_id.required' => __('invoices.validation.payment_status_required'),
            
            // Supplier/issuer validation
            'supplier_id.required_without' => __('invoices.validation.supplier_required_without'),
            'name.required_without' => __('invoices.validation.supplier_name_required'),
            'name.min' => __('invoices.validation.supplier_name_min'),
            'street.required_without' => __('invoices.validation.street_required'),
            'city.required_without' => __('invoices.validation.city_required'),
            'zip.required_without' => __('invoices.validation.zip_required'),
            'country.required_without' => __('invoices.validation.country_required'),
            
            // Client validation
            'client_id.required_without' => __('invoices.validation.client_required_without'),
            'client_name.required_without' => __('invoices.validation.client_name_required'),
            'client_name.min' => __('invoices.validation.client_name_min'),
            'client_street.required_without' => __('invoices.validation.client_street_required'),
            'client_city.required_without' => __('invoices.validation.client_city_required'),
            'client_zip.required_without' => __('invoices.validation.client_zip_required'),
            'client_country.required_without' => __('invoices.validation.client_country_required'),
        ];
    }
    
    /**
     * Prepare the data for validation.
     * This method merges fallback values for form fields when original ones are missing.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Merge data from fallback fields if the original fields are empty
        $input = $this->all();
        
        // Handle country fields
        if (empty($input['country']) && isset($input['country_fallback'])) {
            $input['country'] = $input['country_fallback'];
        }
        
        if (empty($input['client_country']) && isset($input['client_country_fallback'])) {
            $input['client_country'] = $input['client_country_fallback'];
        }
        
        // Handle bank_code field
        if (empty($input['bank_code']) && isset($input['bank_code_fallback'])) {
            $input['bank_code'] = $input['bank_code_fallback'];
        }
        
        // Handle any other fallback fields
        $fallbackFields = [
            'payment_method_id', 'supplier_id', 'client_id', 'due_in', 'payment_status_id'
        ];
        
        foreach ($fallbackFields as $field) {
            if (empty($input[$field]) && isset($input[$field . '_fallback'])) {
                $input[$field] = $input[$field . '_fallback'];
            }
        }
        
        $this->replace($input);
    }
}

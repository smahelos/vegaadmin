<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id', 'required_without:name'],
            'client_id' => 'required|exists:clients,id',
            'invoice_vs' => 'required|string|max:255',
            'invoice_ks' => 'nullable|string|max:255',
            'invoice_ss' => 'nullable|string|max:255',
            'due_in' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|string|max:100',
            'payment_currency' => 'required|string|max:100',
            'issue_date' => 'required|date',
            'tax_point_date' => 'nullable|date',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'invoice_text' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'client_id' => __('invoices.fields.client'),
            'supplier_id' => __('invoices.fields.supplier'),
            'invoice_vs' => __('invoices.fields.invoice_number'),
            'payment_amount' => __('invoices.fields.amount'),
            'issue_date' => __('invoices.fields.issue_date'),
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
            'supplier_id.required_without' => __('invoices.validation.supplier_required'),
            'name.required_without' => __('invoices.validation.supplier_required'),
            'client_id.required' => __('invoices.validation.client_required'),
            'user_id.required' => __('invoices.validation.user_required'),
        ];
    }
}

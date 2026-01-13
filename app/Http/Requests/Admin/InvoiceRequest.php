<?php

namespace App\Http\Requests\Admin;

class InvoiceRequest extends BaseEntityRequest
{
    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'invoice';
    }

    /**
     * Get required permission for invoice operations
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_invoice';
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
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
            'user_id' => 'required|exists:users,id',
            'invoice_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:2048',
            'template' => 'nullable|string|in:default,modern,minimal', // Add template validation
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
            // Use generic keys for consistency with tests and other request
            'client_id' => __('invoices.fields.client'),
            'supplier_id' => __('invoices.fields.supplier'),
            'invoice_vs' => __('invoices.fields.invoice_number'),
            'payment_amount' => __('invoices.fields.amount'),
            'issue_date' => __('invoices.fields.issue_date'),
            'invoice_logo' => __('invoices.fields.invoice_logo'),
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
            'supplier_id.required_without' => __('invoices.validation.supplier_required'),
            'name.required_without' => __('invoices.validation.supplier_required'),
            'client_id.required' => __('invoices.validation.client_required'),
            'user_id.required' => __('invoices.validation.user_required'),
            'invoice_logo.file' => __('invoices.validation.invoice_logo_file'),
            'invoice_logo.mimes' => __('invoices.validation.invoice_logo_format'),
            'invoice_logo.max' => __('invoices.validation.invoice_logo_size'),
        ];
    }
}

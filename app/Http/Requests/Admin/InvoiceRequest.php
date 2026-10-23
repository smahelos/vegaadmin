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
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
                'required_without:name',
            ],
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

    public function attributes(): array
    {
        return [
            'supplier_id' => __('invoices.fields.supplier_id'),
            'client_id' => __('invoices.fields.client_id'),
            'invoice_vs' => __('invoices.fields.invoice_vs'),
            'invoice_ks' => __('invoices.fields.invoice_ks'),
            'invoice_ss' => __('invoices.fields.invoice_ss'),
            'due_in' => __('invoices.fields.due_in'),
            'payment_method_id' => __('invoices.fields.payment_method_id'),
            'payment_amount' => __('invoices.fields.payment_amount'),
            'payment_status' => __('invoices.fields.status'),
            'payment_currency' => __('invoices.fields.payment_currency'),
            'issue_date' => __('invoices.fields.issue_date'),
            'tax_point_date' => __('invoices.fields.tax_point_date'),
            'ico' => __('invoices.fields.supplier_ico'),
            'dic' => __('invoices.fields.supplier_dic'),
            'street' => __('invoices.fields.supplier_street'),
            'city' => __('invoices.fields.supplier_city'),
            'zip' => __('invoices.fields.supplier_zip'),
            'country' => __('invoices.fields.supplier_country'),
            'invoice_text' => __('invoices.fields.invoice_text'),
            'user_id' => __('invoices.fields.user_id'),
            'invoice_logo' => __('invoices.fields.invoice_logo'),
            'template' => __('invoices.fields.template'),
        ];
    }

    /**
     * Choosing an existing supplier or filling in a new one is one decision; the standard
     * required_without message cannot say that.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required_without' => __('invoices.validation.supplier_required_without'),
        ];
    }
}

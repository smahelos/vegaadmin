<?php

namespace App\Infrastructure\Forms\Invoice;

use App\Models\PaymentMethod;
use App\Models\Status;

trait InvoiceFormFields
{
    /**
     * Get invoice form field definitions; the parties come from the party picker
     *
     * @param  array  $paymentMethods  Payment method options for dropdowns
     * @param  array  $statuses  Status options for dropdowns
     * @param  array  $currencies  Currency options for dropdowns
     */
    protected function getInvoiceFields(
        array $paymentMethods = [],
        array $statuses = [],
        array $currencies = [],
    ): array {
        // Default currencies if not provided
        if (empty($currencies)) {
            $currencies = [
                'CZK' => 'CZK',
                'EUR' => 'EUR',
                'USD' => 'USD',
            ];
        }

        return [
            // INVOICE data
            // we want to have Supplier logo at first place
            [
                'name' => 'invoice_logo',
                'label' => __('invoices.fields.invoice_logo'),
                'type' => 'file',
                'hint' => __('invoices.hints.invoice_logo'),
                'accept' => 'image/*',
            ],
            [
                'name' => 'template',
                'label' => __('invoices.fields.template'),
                'type' => 'hidden',
                'default' => 'default',
                'hint' => __('invoices.hints.template'),
            ],
            [
                'name' => 'invoice_vs',
                'label' => __('invoices.fields.invoice_vs_long'),
                'type' => 'text',
                'required' => true,
                'hint' => __('invoices.hints.invoice_vs'),
            ],
            [
                'name' => 'payment_status_id',
                'label' => __('invoices.fields.status'),
                'type' => 'select',
                'options' => $statuses,
                'required' => true,
                'entity' => 'paymentStatus',
                'attribute' => 'name',
                'hint' => __('invoices.hints.status'),
                'model' => Status::class,
            ],
            [
                'name' => 'invoice_ks',
                'label' => __('invoices.fields.invoice_ks'),
                'type' => 'text',
                'hint' => __('invoices.hints.invoice_ks'),
            ],
            [
                'name' => 'invoice_ss',
                'label' => __('invoices.fields.invoice_ss'),
                'type' => 'text',
                'hint' => __('invoices.hints.invoice_ss'),
            ],
            [
                'name' => 'issue_date',
                'label' => __('invoices.fields.issue_date'),
                'type' => 'date',
                'default' => date('Y-m-d'),
                'hint' => __('invoices.hints.issue_date'),
                'required' => true,
            ],
            [
                'name' => 'tax_point_date',
                'label' => __('invoices.fields.tax_point_date'),
                'type' => 'date',
                'hint' => __('invoices.hints.tax_point_date'),
                'default' => date('Y-m-d'),
                'required' => true,
            ],
            [
                'name' => 'payment_method_id',
                'label' => __('invoices.fields.payment_method'),
                'type' => 'select',
                'options' => $paymentMethods,
                'required' => true,
                'entity' => 'paymentMethod',
                'attribute' => 'name',
                'model' => PaymentMethod::class,
                'hint' => __('invoices.hints.payment_method'),
            ],
            [
                'name' => 'due_in',
                'label' => __('invoices.fields.due_in'),
                'type' => 'select_from_array',
                'options' => [
                    1 => '1 ' . __('invoices.units.days'),
                    3 => '3 ' . __('invoices.units.days'),
                    7 => '7 ' . __('invoices.units.days'),
                    14 => '14 ' . __('invoices.units.days'),
                    30 => '30 ' . __('invoices.units.days'),
                ],
                'hint' => __('invoices.hints.due_in'),
                'required' => true,
            ],
            [
                'name' => 'payment_amount',
                'label' => __('invoices.fields.payment_amount'),
                'type' => 'number',
                'default' => 0,
                'hint' => __('invoices.hints.payment_amount'),
                'required' => true,
            ],
            [
                'name' => 'payment_currency',
                'label' => __('invoices.fields.payment_currency'),
                'type' => 'select_from_array',
                'options' => $currencies,
                'hint' => __('invoices.hints.payment_currency'),
                'placeholder' => __('invoices.placeholders.select_currency'),
                'required' => true,
            ],
            [
                'name' => 'invoice_text',
                'label' => __('invoices.fields.invoice_text'),
                'type' => 'textarea',
                'hint' => __('invoices.hints.invoice_text'),
            ],
        ];
    }
}

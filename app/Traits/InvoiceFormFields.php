<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Contracts\CountryServiceInterface;
use Illuminate\Support\Facades\App;

trait InvoiceFormFields
{
    /**
     * Get invoice form field definitions
     *
     * @param array $clients Client options for dropdowns
     * @param array $suppliers Supplier options for dropdowns
     * @param array $paymentMethods Payment method options for dropdowns
     * @param array $statuses Status options for dropdowns
     * @param array $currencies Currency options for dropdowns
     * @return array
     */
    protected function getInvoiceFields($clients = [], $suppliers = [], $paymentMethods = [], $statuses = [], $currencies = [])
    {
        // Get country codes from CountryService
        $countries = App::make(CountryServiceInterface::class)->getCountryCodesForSelect();
        
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
            [
                'name' => 'invoice_vs',
                'label' => __('invoices.fields.invoice_vs_long'),
                'type' => 'text',
                'required' => true,
                'hint' => __('invoices.hints.invoice_vs'),
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
                    30 => '30 ' . __('invoices.units.days')
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
                'name' => 'invoice_text',
                'label' => __('invoices.fields.invoice_text'),
                'type' => 'textarea',
                'hint' => __('invoices.hints.invoice_text'),
            ],
            // SUPPLIER data
            [
                'name' => 'supplier_id',
                'label' => __('invoices.fields.supplier_id'),
                'type' => 'select',
                'options' => $suppliers,
                'required' => true,
                'entity' => 'supplier',
                'attribute' => 'name',
                'hint' => __('suppliers.hints.supplier'),
                'placeholder' => __('invoices.placeholders.select_supplier'),
                'model' => Supplier::class,
            ],
            [
                'name' => 'name',
                'label' => __('suppliers.fields.name'),
                'type' => 'text',
                'hint' => __('suppliers.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => __('suppliers.fields.email'),
                'type' => 'text',
                'hint' => __('suppliers.hints.email'),
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('suppliers.fields.phone'),
                'type' => 'text',
                'hint' => __('suppliers.hints.phone'),
                'required' => false,
            ],
            [
                'name' => 'street',
                'label' => __('suppliers.fields.street'),
                'type' => 'text',
                'hint' => __('suppliers.hints.street'),
                'required' => true,
            ],
            [
                'name' => 'city',
                'label' => __('suppliers.fields.city'),
                'type' => 'text',
                'hint' => __('suppliers.hints.city'),
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => __('suppliers.fields.zip'),
                'type' => 'text',
                'hint' => __('suppliers.hints.zip'),
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => __('suppliers.fields.country'),
                'default' => 'Česká republika',
                'type' => 'select_from_array',
                'options' => $countries,
                'hint' => __('suppliers.hints.country'),
                'placeholder' => __('general.placeholders.select_country'),
                'required' => true,
            ],
            [
                'name' => 'ico',
                'label' => __('suppliers.fields.ico'),
                'type' => 'text',
                'hint' => __('suppliers.hints.ico'),
            ],
            [
                'name' => 'dic',
                'label' => __('suppliers.fields.dic'),
                'type' => 'text',
                'hint' => __('suppliers.hints.dic'),
            ],
            // Payment information
            [
                'name' => 'account_number',
                'label' => __('suppliers.fields.account_number'),
                'type' => 'text',
                'hint' => __('suppliers.hints.account_number'),
                'placeholder' => __('suppliers.placeholders.account_number'),
            ],
            [
                'name' => 'bank_code',
                'label' => __('suppliers.fields.bank_code'),
                'type' => 'text',
                'hint' => __('suppliers.hints.bank_code'),
                'placeholder' => __('suppliers.placeholders.bank_code'),
            ],
            [
                'name' => 'bank_name',
                'label' => __('suppliers.fields.bank_name'),
                'type' => 'text',
                'hint' => __('suppliers.hints.bank_name'),
                'placeholder' => __('suppliers.placeholders.bank_name'),
            ],
            [
                'name' => 'iban',
                'label' => __('suppliers.fields.iban'),
                'type' => 'text',
                'hint' => __('suppliers.hints.iban'),
                'placeholder' => __('suppliers.placeholders.iban'),
            ],
            [
                'name' => 'swift',
                'label' => __('suppliers.fields.swift'),
                'type' => 'text',
                'hint' => __('suppliers.hints.swift'),
                'placeholder' => __('suppliers.placeholders.swift'),
            ],
            // CLIENT data
            [
                'name' => 'client_id',
                'label' => __('invoices.fields.client_id'),
                'type' => 'select',
                'options' => $clients,
                'required' => true,
                'entity' => 'client',
                'attribute' => 'name',
                'placeholder' => __('invoices.placeholders.select_client'),
                'model' => Client::class,
                'hint' => __('clients.hints.client'),
            ],
            [
                'name' => 'client_name',
                'label' => __('invoices.fields.client_name'),
                'type' => 'text',
                'hint' => __('clients.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'client_email',
                'label' => __('clients.fields.email'),
                'type' => 'text',
                'hint' => __('clients.hints.email'),
                'required' => false,
            ],
            [
                'name' => 'client_phone',
                'label' => __('clients.fields.phone'),
                'type' => 'text',
                'hint' => __('clients.hints.phone'),
                'required' => false,
            ],
            [
                'name' => 'client_street',
                'label' => __('clients.fields.street'),
                'type' => 'text',
                'hint' => __('clients.hints.street'),
                'required' => true,
            ],
            [
                'name' => 'client_city',
                'label' => __('clients.fields.city'),
                'type' => 'text',
                'hint' => __('clients.hints.city'),
                'required' => true,
            ],
            [
                'name' => 'client_zip',
                'label' => __('clients.fields.zip'),
                'type' => 'text',
                'hint' => __('clients.hints.zip'),
                'required' => true,
            ],
            [
                'name' => 'client_country',
                'label' => __('clients.fields.country'),
                'default' => 'Česká republika',
                'type' => 'select_from_array',
                'options' => $countries,
                'hint' => __('clients.hints.country'),
                'placeholder' => __('general.placeholders.select_country'),
                'required' => true,
            ],
            [
                'name' => 'client_ico',
                'label' => __('clients.fields.ico'),
                'type' => 'text',
                'hint' => __('clients.hints.ico'),
                'required' => false,
            ],
            [
                'name' => 'client_dic',
                'label' => __('clients.fields.dic'),
                'type' => 'text',
                'hint' => __('clients.hints.dic'),
                'required' => false,
            ],
        ];
    }
}

<?php

namespace App\Traits;

trait SupplierFormFields
{
    /**
     * Get supplier form fields definitions
     *
     * @return array
     */
    protected function getSupplierFields(): array
    {
        return [
            [
                'name' => 'name',
                'label' => __('suppliers.fields.name'),
                'type' => 'text',
                'hint' => __('suppliers.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'shortcut',
                'label' => __('suppliers.fields.shortcut'),
                'type' => 'text',
                'hint' => __('suppliers.hints.shortcut'),
            ],
            [
                'name' => 'email',
                'label' => __('suppliers.fields.email'),
                'type' => 'email',
                'hint' => __('suppliers.hints.email'),
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => __('suppliers.fields.phone'),
                'type' => 'text',
                'hint' => __('suppliers.hints.phone'),
            ],
            [
                'name' => 'street',
                'label' => __('suppliers.fields.street'),
                'required' => true,
                'hint' => __('suppliers.hints.street'),
                'type' => 'text',
            ],
            [
                'name' => 'city',
                'label' => __('suppliers.fields.city'),
                'required' => true,
                'hint' => __('suppliers.hints.city'),
                'type' => 'text',
            ],
            [
                'name' => 'zip',
                'label' => __('suppliers.fields.zip'),
                'required' => true,
                'hint' => __('suppliers.hints.zip'),
                'type' => 'text',
            ],
            [
                'name' => 'country',
                'label' => __('suppliers.fields.country'),
                'required' => true,
                'hint' => __('suppliers.hints.country'),
                'type' => 'text',
                'placeholder' => __('suppliers.placeholders.select_country'),
            ],
            [
                'name' => 'ico',
                'label' => __('suppliers.fields.ico'),
                'type' => 'text',
                'hint' => __('suppliers.validation.ico_format'),
            ],
            [
                'name' => 'dic',
                'label' => __('suppliers.fields.dic'),
                'type' => 'text',
                'hint' => __('suppliers.hints.dic'),
            ],
            [
                'name' => 'description',
                'label' => __('suppliers.fields.description'),
                'type' => 'textarea',
                'hint' => __('suppliers.hints.description'),
            ],
            [
                'name' => 'is_default',
                'label' => __('suppliers.fields.is_default'),
                'type' => 'checkbox',
                'hint' => __('suppliers.messages.is_default_explanation'),
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
        ];
    }
}

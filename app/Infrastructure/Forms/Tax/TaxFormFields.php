<?php

namespace App\Infrastructure\Forms\Tax;

trait TaxFormFields
{
    /**
     * Get tax form fields definitions
     */
    protected function getTaxFields(): array
    {
        return [
            [
                'name' => 'name',
                'label' => __('taxes.fields.name'),
                'type' => 'text',
                'hint' => __('taxes.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'rate',
                'label' => __('taxes.fields.rate'),
                'type' => 'number',
                'default' => 0,
                'hint' => __('taxes.hints.rate'),
                'required' => true,
            ],
            [
                'name' => 'slug',
                'label' => __('taxes.fields.slug'),
                'type' => 'text',
                'hint' => __('taxes.hints.slug'),
                'required' => true,
            ],
            [
                'name' => 'description',
                'label' => __('taxes.fields.description'),
                'type' => 'textarea',
                'hint' => __('taxes.hints.description'),
                'required' => false,
            ],
        ];
    }
}

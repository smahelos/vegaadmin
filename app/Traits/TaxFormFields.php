<?php

namespace App\Traits;

trait TaxFormFields
{
    /**
     * Get tax form fields definitions
     *
     * @return array
     */
    protected function getTaxFields(): array
    {
        return [
            [
                'name' => 'name',
                'label' => __('tax.fields.name'),
                'type' => 'text',
                'hint' => __('tax.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'rate',
                'label' => __('tax.fields.rate'),
                'type' => 'number',
                'default' => 0,
                'hint' => __('tax.hints.rate'),
                'required' => true,
            ],
            [
                'name' => 'slug',
                'label' => __('tax.fields.slug'),
                'type' => 'text',
                'hint' => __('tax.hints.slug'),
                'required' => true,
            ],
            [
                'name' => 'description',
                'label' => __('tax.fields.description'),
                'type' => 'textarea',
                'hint' => __('tax.hints.description'),
                'required' => false,
            ],
        ];
    }
}

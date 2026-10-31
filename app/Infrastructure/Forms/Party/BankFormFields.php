<?php

namespace App\Infrastructure\Forms\Party;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;

trait BankFormFields
{
    /**
     * Get bank form fields definitions
     */
    protected function getBankFields(): array
    {
        $countries = app(CountryOptionsPresenter::class)->options(app()->getLocale());

        return [
            [
                'name' => 'name',
                'label' => __('banks.fields.name'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'code',
                'label' => __('banks.fields.code'),
                'type' => 'text',
                'hint' => __('banks.hints.code'),
                'required' => true,
            ],
            [
                'name' => 'swift',
                'label' => __('banks.fields.swift'),
                'type' => 'text',
                'hint' => __('banks.hints.swift'),
                'required' => false,
            ],
            [
                'name' => 'country',
                'label' => __('banks.fields.country'),
                'default' => 'CZ',
                'type' => 'select_from_array',
                'options' => $countries,
                'required' => true,
                'allows_null' => false,
            ],
            [
                'name' => 'active',
                'label' => __('banks.fields.active'),
                'type' => 'boolean',
                'default' => true,
            ],
            [
                'name' => 'description',
                'label' => __('banks.fields.description'),
                'type' => 'textarea',
                'required' => false,
            ],
        ];
    }
}

<?php

namespace App\Infrastructure\Forms\Party;

use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use Illuminate\Support\Facades\App;

trait BankFormFields
{
    /**
     * Get bank form fields definitions
     *
     * @return array
     */
    protected function getBankFields(): array
    {
        // Get country codes from CountryService
        $countries = App::make(CountryServiceInterface::class)->getCountryCodesForSelect();

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

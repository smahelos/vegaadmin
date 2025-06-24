<?php

namespace App\Traits;
use App\Contracts\CountryServiceInterface;
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
                'label' => __('bank.fields.name'),
                'type' => 'text',
                'hint' => __('bank.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'code',
                'label' => __('bank.fields.code'),
                'type' => 'text',
                'hint' => __('bank.hints.code'),
                'required' => true,
            ],
            [
                'name' => 'swift',
                'label' => __('bank.fields.swift'),
                'type' => 'text',
                'hint' => __('bank.hints.swift'),
                'required' => false,
            ],
            [
                'name' => 'country',
                'label' => __('bank.fields.country'),
                'default' => 'cz',
                'type' => 'select_from_array',
                'options' => $countries,
                'hint' => __('suppliers.hints.country'),
                'required' => true,
                'allows_null' => false,
            ],
            [
                'name' => 'active',
                'label' => __('bank.fields.active'),
                'type' => 'boolean',
                'hint' => __('bank.hints.active'),
                'default' => true,
            ],
            [
                'name' => 'description',
                'label' => __('bank.fields.description'),
                'type' => 'textarea',
                'hint' => __('bank.hints.description'),
                'required' => false,
            ],
        ];
    }
}

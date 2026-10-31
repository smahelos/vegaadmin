<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Party;

/**
 * Admin form fields of the party profile and address that businesses and contacts share.
 */
final class PartyFormFields
{
    /** @return list<array<string, mixed>> */
    public static function logo(): array
    {
        return [
            [
                'name' => 'logo',
                'label' => __('parties.fields.logo'),
                'type' => 'party_logo',
                'upload' => true,
                'hint' => __('parties.hints.logo'),
            ],
            [
                'name' => 'remove_logo',
                'label' => __('parties.actions.remove_logo'),
                'type' => 'checkbox',
            ],
        ];
    }

    /**
     * @param  array<string, string>  $countries  country name by code
     * @param  array<string, string|null>  $values  current values of fields the model does not hold as attributes
     * @return list<array<string, mixed>>
     */
    public static function profile(array $countries, array $values = []): array
    {
        $half = ['class' => 'form-group col-md-6'];

        return [
            [
                'name' => 'name',
                'label' => __('parties.fields.name'),
                'type' => 'text',
                'wrapper' => $half,
            ],
            [
                'name' => 'short_name',
                'label' => __('parties.fields.short_name'),
                'type' => 'text',
                'hint' => __('parties.hints.short_name'),
                'wrapper' => $half,
            ],
            [
                'name' => 'registration_country',
                'label' => __('parties.fields.registration_country'),
                'type' => 'select_from_array',
                'options' => $countries,
                'allows_null' => true,
                'wrapper' => $half,
            ],
            [
                'name' => 'company_number',
                'label' => __('parties.fields.company_number'),
                'type' => 'text',
                'hint' => __('parties.hints.company_number'),
                'wrapper' => $half,
            ],
            [
                'name' => 'vat_number',
                'label' => __('parties.fields.vat_number'),
                'type' => 'text',
                'wrapper' => $half,
            ],
            [
                'name' => 'email',
                'label' => __('parties.fields.email'),
                'type' => 'email',
                'wrapper' => $half,
            ],
            [
                'name' => 'phone',
                'label' => __('parties.fields.phone'),
                'type' => 'text',
                'wrapper' => $half,
            ],
            [
                'name' => 'street',
                'label' => __('parties.fields.street'),
                'type' => 'text',
                'value' => $values['street'] ?? null,
                'wrapper' => $half,
            ],
            [
                'name' => 'city',
                'label' => __('parties.fields.city'),
                'type' => 'text',
                'value' => $values['city'] ?? null,
                'wrapper' => $half,
            ],
            [
                'name' => 'postal_code',
                'label' => __('parties.fields.postal_code'),
                'type' => 'text',
                'value' => $values['postal_code'] ?? null,
                'wrapper' => $half,
            ],
            [
                'name' => 'country',
                'label' => __('parties.fields.country'),
                'type' => 'select_from_array',
                'options' => $countries,
                'allows_null' => true,
                'value' => $values['country'] ?? 'CZ',
                'wrapper' => $half,
            ],
            [
                'name' => 'note',
                'label' => __('parties.fields.note'),
                'type' => 'textarea',
                'hint' => __('parties.hints.note'),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $countries
     * @return list<array<string, mixed>>
     */
    public static function bankAccount(array $countries): array
    {
        $half = ['class' => 'form-group col-md-6'];

        return [
            [
                'name' => 'label',
                'label' => __('parties.bank_accounts.fields.label'),
                'type' => 'text',
                'hint' => __('parties.bank_accounts.hints.label'),
                'wrapper' => $half,
            ],
            [
                'name' => 'country',
                'label' => __('parties.bank_accounts.fields.country'),
                'type' => 'select_from_array',
                'options' => $countries,
                'allows_null' => true,
                'default' => 'CZ',
                'wrapper' => $half,
            ],
            [
                'name' => 'iban',
                'label' => __('parties.bank_accounts.fields.iban'),
                'type' => 'text',
                'hint' => __('parties.bank_accounts.hints.iban'),
            ],
            [
                'name' => 'number',
                'label' => __('parties.bank_accounts.fields.number'),
                'type' => 'text',
                'hint' => __('parties.bank_accounts.hints.number'),
                'wrapper' => $half,
            ],
            [
                'name' => 'bank_code',
                'label' => __('parties.bank_accounts.fields.bank_code'),
                'type' => 'text',
                'wrapper' => $half,
            ],
            [
                'name' => 'swift',
                'label' => __('parties.bank_accounts.fields.swift'),
                'type' => 'text',
                'wrapper' => $half,
            ],
            [
                'name' => 'bank_name',
                'label' => __('parties.bank_accounts.fields.bank_name'),
                'type' => 'text',
                'wrapper' => $half,
            ],
        ];
    }
}

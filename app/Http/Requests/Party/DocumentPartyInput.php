<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;
use App\Domain\Payment\ValueObjects\BankAccount;

/**
 * The party and account fields of a document, `<role>_*` for the supplier and the customer and `account_*` for
 * the payment account, validated with the rules of businesses and contacts.
 */
final class DocumentPartyInput
{
    public const FIELDS = [
        'name',
        'registration_country',
        'company_number',
        'vat_number',
        'street',
        'city',
        'postal_code',
        'country',
        'email',
        'phone',
    ];

    private const ADDRESS_FIELDS = [
        'street',
        'city',
        'postal_code',
        'country',
    ];

    private const ACCOUNT_FIELDS = [
        'iban',
        'number',
        'bank_code',
        'country',
        'swift',
        'bank_name',
    ];

    /**
     * @param  array<string, mixed>  $input  the whole request input
     * @return array<string, list<string|object>>
     */
    public static function rules(
        array $input,
        string $role,
        bool $addressRequired,
    ): array {
        $rules = PartyInput::rules(self::fieldsOf($input, $role . '_'), fn (string $field): string => $field);

        $keyed = [];
        foreach (self::FIELDS as $field) {
            $fieldRules = $rules[$field];
            if ($addressRequired && in_array(
                $field,
                self::ADDRESS_FIELDS,
                true,
            )) {
                $fieldRules = ['required', ...$fieldRules];
            }
            $keyed[$role . '_' . $field] = $fieldRules;
        }

        return $keyed;
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(string $role): array
    {
        $attributes = [];
        foreach (self::FIELDS as $field) {
            $attributes[$role . '_' . $field] = __('parties.fields.' . $field);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $input  validated input
     */
    public static function party(array $input, string $role): DocumentPartyDTO
    {
        $fields = self::fieldsOf($input, $role . '_');

        return new DocumentPartyDTO(
            name: PartyInput::text($fields, 'name') ?? '',
            registrationNumber: RegistrationNumber::fromParts(
                PartyInput::country($fields, 'registration_country'),
                PartyInput::text($fields, 'company_number'),
            ),
            vatNumber: VatNumber::fromNullable(PartyInput::text($fields, 'vat_number')),
            address: Address::fromParts(
                PartyInput::text($fields, 'street'),
                PartyInput::text($fields, 'city'),
                PartyInput::text($fields, 'postal_code'),
                PartyInput::country($fields, 'country'),
            ),
            email: PartyInput::text($fields, 'email'),
            phone: PartyInput::text($fields, 'phone'),
        );
    }

    /**
     * A one-off account, needed only for a bank transfer: otherwise the account is left out of the validated input,
     * so details the form hides never reach the document.
     *
     * @param  array<string, mixed>  $input  the whole request input
     * @return array<string, list<string|object>>
     */
    public static function accountRules(array $input, bool $bankTransfer): array
    {
        if (! $bankTransfer) {
            return array_fill_keys(
                array_map(fn (string $field): string => 'account_' . $field, self::ACCOUNT_FIELDS),
                ['exclude'],
            );
        }

        $fields = self::fieldsOf($input, 'account_');
        $rules = BankAccountInput::rules($fields, fn (string $field): string => 'account_' . $field);
        unset($rules['account_label']);

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function accountAttributes(): array
    {
        $attributes = [];
        foreach (self::ACCOUNT_FIELDS as $field) {
            $attributes['account_' . $field] = __('parties.bank_accounts.fields.' . $field);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $input  validated input
     */
    public static function account(array $input): ?DocumentAccountDTO
    {
        $fields = self::fieldsOf($input, 'account_');
        if (! BankAccountInput::isFilled($fields)) {
            return null;
        }

        return new DocumentAccountDTO(
            BankAccount::fromParts(
                PartyInput::text($fields, 'iban'),
                PartyInput::text($fields, 'number'),
                PartyInput::text($fields, 'bank_code'),
                PartyInput::country($fields, 'country'),
                PartyInput::text($fields, 'swift'),
            ),
            PartyInput::text($fields, 'bank_name'),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function fieldsOf(array $input, string $prefix): array
    {
        $fields = [];
        foreach ($input as $key => $value) {
            if (is_string($key) && str_starts_with($key, $prefix)) {
                $fields[substr($key, strlen($prefix))] = $value;
            }
        }

        return $fields;
    }
}

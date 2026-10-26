<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Http\Requests\Rules\BankAccountRule;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The bank account fields of a business or contact, shared by the Livewire forms and the admin requests in the way
 * PartyInput is. A bank code is one of the active banks of the account's country.
 */
final class BankAccountInput
{
    public const FIELDS = [
        'label',
        'iban',
        'number',
        'bank_code',
        'country',
        'swift',
        'bank_name',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string): string  $key
     * @return array<string, list<string|object>>
     */
    public static function rules(array $input, Closure $key): array
    {
        $country = PartyInput::country($input, 'country');
        $iban = PartyInput::text($input, 'iban');
        $account = new BankAccountRule(
            $iban,
            PartyInput::text($input, 'number'),
            PartyInput::text($input, 'bank_code'),
            $country,
            PartyInput::text($input, 'swift'),
        );

        // The whole account is checked on the IBAN, or on the local number when there is no IBAN
        $rules = [
            'label' => [
                'nullable',
                'string',
                'max:255',
            ],
            'iban' => $iban === null ? ['nullable'] : [
                'string',
                'max:50',
                $account,
            ],
            'number' => $iban === null ? [
                'required',
                'string',
                'max:20',
                $account,
            ] : [
                'nullable',
                'string',
                'max:20',
            ],
            'bank_code' => [
                'nullable',
                'string',
                Rule::exists('banks', 'code')
                    ->where('country', $country->value ?? '')
                    ->where('active', true),
            ],
            'country' => [
                'nullable',
                Rule::enum(Country::class),
            ],
            'swift' => [
                'nullable',
                'string',
                'max:11',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        $keyed = [];
        foreach ($rules as $field => $fieldRules) {
            $keyed[$key($field)] = $fieldRules;
        }

        return $keyed;
    }

    /**
     * @param  Closure(string): string  $key
     * @return array<string, string>
     */
    public static function attributes(Closure $key): array
    {
        $attributes = [];
        foreach (self::FIELDS as $field) {
            $attributes[$key($field)] = __('parties.bank_accounts.fields.' . $field);
        }

        return $attributes;
    }

    /**
     * Whether any account detail was entered; the label or bank name alone is not an account.
     *
     * @param  array<string, mixed>  $input
     */
    public static function isFilled(array $input): bool
    {
        return PartyInput::text($input, 'iban') !== null
            || PartyInput::text($input, 'number') !== null
            || PartyInput::text($input, 'bank_code') !== null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function writeData(array $input): BankAccountWriteData
    {
        return new BankAccountWriteData(
            label: PartyInput::text($input, 'label'),
            account: BankAccount::fromParts(
                PartyInput::text($input, 'iban'),
                PartyInput::text($input, 'number'),
                PartyInput::text($input, 'bank_code'),
                PartyInput::country($input, 'country'),
                PartyInput::text($input, 'swift'),
            ),
            bankName: PartyInput::text($input, 'bank_name'),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function fromAccount(BankAccountDTO $account): array
    {
        return [
            'label' => $account->label ?? '',
            'iban' => $account->account->iban,
            'number' => $account->account->number ?? '',
            'bank_code' => $account->account->bankCode ?? '',
            'country' => $account->account->country->value,
            'swift' => $account->account->swift ?? '',
            'bank_name' => $account->bankName ?? '',
        ];
    }
}

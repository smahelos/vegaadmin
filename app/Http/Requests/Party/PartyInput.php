<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Http\Requests\Rules\RegistrationNumberRule;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The party profile fields businesses, contacts and document parties share: one rule set for the Livewire forms
 * and the admin requests. Input and field names are the snake_case request keys; $key turns a field name into the
 * key the caller validates (a Livewire form property, a request key).
 */
final class PartyInput
{
    public const FIELDS = [
        'name',
        'short_name',
        'registration_country',
        'company_number',
        'vat_number',
        'email',
        'phone',
        'note',
        'street',
        'city',
        'postal_code',
        'country',
    ];

    private const ADDRESS_PARTS = [
        'street',
        'city',
        'postal_code',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string): string  $key
     * @return array<string, list<string|object>>
     */
    public static function rules(array $input, Closure $key): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'registration_country' => [
                Rule::requiredIf(self::filled($input, 'company_number')),
                'nullable',
                Rule::enum(Country::class),
            ],
            'company_number' => [
                'nullable',
                'string',
                'max:255',
                new RegistrationNumberRule(self::country($input, 'registration_country')),
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],
            'note' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'country' => [
                Rule::requiredIf(self::hasAddress($input)),
                'nullable',
                Rule::enum(Country::class),
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
            $attributes[$key($field)] = __('parties.fields.' . $field);
        }

        return $attributes;
    }

    /**
     * Validated input as the profile; an emptied field is null, so saving stores it as empty.
     *
     * @param  array<string, mixed>  $input
     */
    public static function profile(array $input): PartyProfile
    {
        return new PartyProfile(
            name: trim(self::text($input, 'name') ?? ''),
            shortName: self::text($input, 'short_name'),
            registrationNumber: RegistrationNumber::fromParts(
                self::country($input, 'registration_country'),
                self::text($input, 'company_number'),
            ),
            vatNumber: VatNumber::fromNullable(self::text($input, 'vat_number')),
            email: self::text($input, 'email'),
            phone: self::text($input, 'phone'),
            note: self::text($input, 'note'),
            address: Address::fromParts(
                self::text($input, 'street'),
                self::text($input, 'city'),
                self::text($input, 'postal_code'),
                self::country($input, 'country'),
            ),
        );
    }

    /**
     * The profile as input of a form, the inverse of profile().
     *
     * @return array<string, string>
     */
    public static function fromProfile(PartyProfile $profile): array
    {
        return [
            'name' => $profile->name,
            'short_name' => $profile->shortName ?? '',
            'registration_country' => $profile->registrationNumber?->country->value ?? '',
            'company_number' => $profile->registrationNumber?->number ?? '',
            'vat_number' => $profile->vatNumber?->value ?? '',
            'email' => $profile->email ?? '',
            'phone' => $profile->phone ?? '',
            'note' => $profile->note ?? '',
            'street' => $profile->address?->street ?? '',
            'city' => $profile->address?->city ?? '',
            'postal_code' => $profile->address?->postalCode ?? '',
            'country' => $profile->address?->country->value ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function text(array $input, string $field): ?string
    {
        $value = $input[$field] ?? null;
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function country(array $input, string $field): ?Country
    {
        return Country::tryFrom(self::text($input, $field) ?? '');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function filled(array $input, string $field): bool
    {
        return self::text($input, $field) !== null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function hasAddress(array $input): bool
    {
        foreach (self::ADDRESS_PARTS as $part) {
            if (self::filled($input, $part)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Shared\Translation\Enums\Language;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The invoicing settings of a business, for the Livewire form and the admin request. Field names are the snake_case
 * request keys; $key turns a field name into the key the caller validates.
 */
final class InvoicingSettingsInput
{
    /**
     * @param  Closure(string): string  $key
     * @return array<string, list<string|object>>
     */
    public static function rules(Closure $key): array
    {
        return [
            $key('is_vat_payer') => [
                'required',
                'boolean',
            ],
            $key('register_entry') => [
                'nullable',
                'string',
                'max:255',
            ],
            $key('document_language') => [
                'required',
                Rule::enum(Language::class),
            ],
        ];
    }

    /**
     * @param  Closure(string): string  $key
     * @return array<string, string>
     */
    public static function attributes(Closure $key): array
    {
        return [
            $key('is_vat_payer') => __('businesses.fields.is_vat_payer'),
            $key('register_entry') => __('businesses.fields.register_entry'),
            $key('document_language') => __('businesses.fields.document_language'),
        ];
    }

    /**
     * Validated input as the settings.
     *
     * @param  array<string, mixed>  $input
     */
    public static function settings(array $input): InvoicingSettings
    {
        $registerEntry = PartyInput::text($input, 'register_entry');

        return new InvoicingSettings(
            vatPayer: (bool) $input['is_vat_payer'],
            registerEntry: $registerEntry,
            documentLanguage: Language::from($input['document_language']),
        );
    }
}

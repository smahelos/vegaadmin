<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Invoice;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentDocumentPartyOptionMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;

final class AdminDocumentFormFields
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function parties(
        string $role,
        Invoice|Expense|null $document = null,
    ): array {
        $fields = [
            [
                'name' => 'user_id',
                'label' => __('parties.fields.owner'),
                'type' => 'select2',
                'entity' => 'user',
                'model' => User::class,
                'attribute' => 'name',
                'default' => backpack_user()?->id,
                'allows_null' => false,
                'wrapper' => ['class' => 'form-group col-12 col-md-6'],
            ],
            self::selection(
                'business_id',
                Business::class,
                'businesses',
                ['user_id'],
                false,
            ),
            self::selection(
                'contact_id',
                Contact::class,
                'contacts',
                ['user_id'],
                true,
            ),
            self::selection(
                'bank_account_id',
                BankAccount::class,
                'accounts',
                [$role === 'customer' ? 'business_id' : 'contact_id', 'user_id'],
                true,
            ),
        ];
        $fields[1]['document_business_role'] = $role === 'customer' ? 'supplier' : 'customer';
        $fields[1]['stored_reference_id'] = $document?->business_id;
        $fields[2]['counterparty_role'] = $role;
        $fields[2]['stored_reference_id'] = $document?->contact_id;
        $fields[3]['document_account'] = true;
        $fields[3]['stored_reference_id'] = $document?->bank_account_id;
        $fields[3]['hint'] = $role === 'customer'
            ? __('parties.hints.document_business_account')
            : __('parties.hints.document_contact_account');
        $fields[2]['hint'] = __('parties.hints.document_contact_copy');
        $fields[3]['display_value'] = EloquentDocumentPartyOptionMapper::label(...);
        $fields[3]['select_default'] = $role === 'customer';
        if ($role === 'customer') {
            $fields[3]['bank_transfer_method_id'] = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->value('id');
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    public static function section(
        string $name,
        string $label,
        ?string $hint = null,
    ): array {
        return [
            'name' => 'section_' . $name,
            'type' => 'document_section',
            'label' => $label,
            'hint' => $hint,
            'wrapper' => ['class' => 'col-12 mt-4 mb-3', 'data-document-section' => $name],
        ];
    }

    /**
     * @param  array<string, string>  $countries
     * @return list<array<string, mixed>>
     */
    public static function counterparty(string $role, array $countries): array
    {
        $profile = array_column(
            PartyFormFields::profile($countries),
            null,
            'name',
        );
        $fields = [];
        foreach ([
            'name' => 12,
            'registration_country' => 4,
            'company_number' => 4,
            'vat_number' => 4,
            'street' => 12,
            'city' => 6,
            'postal_code' => 3,
            'country' => 3,
            'email' => 6,
            'phone' => 6,
        ] as $name => $width) {
            $field = $profile[$name];
            $field['name'] = $role . '_' . $name;
            $field['wrapper'] = ['class' => 'form-group col-12 col-md-' . $width];
            unset($field['value']);
            if ($field['type'] === 'select_from_array') {
                $field['type'] = 'select2_from_array';
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    public static function businessPreview(string $role, Invoice|Expense|null $document): array
    {
        $items = [];
        foreach ([
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
        ] as $name) {
            $items[] = [
                'key' => $name,
                'label' => __('parties.fields.' . $name),
                'value' => $document?->getAttribute($role . '_' . $name),
            ];
        }

        return [
            'name' => 'document_business_profile',
            'type' => 'document_profile',
            'profile' => 'business',
            'items' => $items,
            'hint' => __('parties.hints.document_business_snapshot'),
        ];
    }

    /**
     * @param  array<string, string>  $countries
     * @return list<array<string, mixed>>
     */
    public static function accountFields(array $countries, bool $oneOff): array
    {
        $profile = array_column(
            PartyFormFields::bankAccount($countries),
            null,
            'name',
        );
        $fields = [];
        foreach ([
            'country' => 3,
            'number' => 5,
            'bank_code' => 4,
            'bank_name' => 12,
            'iban' => 8,
            'swift' => 4,
        ] as $name => $width) {
            $field = $profile[$name];
            $field['name'] = 'account_' . $name;
            $field['wrapper'] = ['class' => 'form-group col-12 col-md-' . $width, 'data-document-account-field' => true];
            unset($field['default']);
            if ($field['type'] === 'select_from_array') {
                $field['type'] = 'select2_from_array';
            }
            if (! $oneOff) {
                $field['attributes']['disabled'] = 'disabled';
                unset($field['hint']);
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param  class-string  $model
     * @param  list<string>  $dependencies
     * @return array<string, mixed>
     */
    private static function selection(
        string $name,
        string $model,
        string $source,
        array $dependencies,
        bool $nullable,
    ): array {
        return [
            'name' => $name,
            'label' => __('invoices.fields.' . $name),
            'type' => 'select2_from_ajax',
            'model' => $model,
            'attribute' => $name === 'bank_account_id' ? 'label' : 'name',
            'entity' => false,
            'data_source' => route('admin.document-parties.' . $source),
            'dependencies' => $dependencies,
            'allows_null' => $nullable,
            'minimum_input_length' => 0,
            'wrapper' => ['class' => 'form-group col-12 col-md-6'],
        ];
    }
}

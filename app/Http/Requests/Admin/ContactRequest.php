<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\DTO\ContactWriteData;
use App\Http\Requests\Party\PartyInput;
use App\Http\Requests\Party\PartyLogoInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A contact saved in the admin, for the user chosen as its owner on creation.
 */
final class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST') ? 'can_create_contact' : 'can_update_contact';

        return backpack_user()?->can($permission) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $rules = PartyInput::rules($this->all(), static fn (string $field): string => $field) + PartyLogoInput::rules() + [
            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        if ($this->isMethod('POST')) {
            $rules['user_id'] = [
                'required',
                'integer',
                'exists:users,id',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return PartyInput::attributes(static fn (string $field): string => $field) + [
            'user_id' => __('parties.fields.owner'),
            'contact_person' => __('contacts.fields.contact_person'),
            'logo' => __('parties.fields.logo'),
        ];
    }

    public function writeData(): ContactWriteData
    {
        $validated = $this->validated();

        return new ContactWriteData(
            PartyInput::profile($validated),
            PartyInput::text($validated, 'contact_person'),
        );
    }
}

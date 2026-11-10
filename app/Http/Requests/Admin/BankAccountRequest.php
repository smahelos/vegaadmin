<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\User\Enums\Permission;
use App\Http\Requests\Party\BankAccountInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A bank account saved in the admin; a new one names its owner, a business or a contact. Changing accounts falls
 * under the owner's update permission.
 */
final class BankAccountRequest extends FormRequest
{
    /**
     * A new account checks the permission of the owner it names; an edit is checked against the stored owner by
     * the controller, which knows the account.
     */
    public function authorize(): bool
    {
        $user = backpack_user();
        if ($user === null) {
            return false;
        }

        if ($this->isMethod('POST') && $this->filled('business_id')) {
            return $user->can(Permission::BUSINESS_UPDATE);
        }

        if ($this->isMethod('POST') && $this->filled('contact_id')) {
            return $user->can(Permission::CONTACT_UPDATE);
        }

        return $user->can(Permission::BUSINESS_UPDATE) || $user->can(Permission::CONTACT_UPDATE);
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $rules = BankAccountInput::rules($this->all(), static fn (string $field): string => $field);

        if ($this->isMethod('POST')) {
            $rules['business_id'] = [
                'nullable',
                'integer',
                'required_without:contact_id',
                'prohibits:contact_id',
                'exists:businesses,id',
            ];
            $rules['contact_id'] = [
                'nullable',
                'integer',
                'required_without:business_id',
                'exists:contacts,id',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return BankAccountInput::attributes(static fn (string $field): string => $field) + [
            'business_id' => __('businesses.entity.singular'),
            'contact_id' => __('contacts.entity.singular'),
        ];
    }

    public function writeData(): BankAccountWriteData
    {
        return BankAccountInput::writeData($this->validated());
    }
}

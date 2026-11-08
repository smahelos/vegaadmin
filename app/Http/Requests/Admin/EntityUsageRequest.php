<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\LimitedEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EntityUsageRequest extends FormRequest
{
    /**
     * Whoever can fill in the entity's form can see the usage shown in it.
     */
    public function authorize(): bool
    {
        $entity = $this->string('entity');
        $permissions = match (LimitedEntity::tryFrom($entity->toString())) {
            LimitedEntity::INVOICE => ['can_create_invoice', 'can_update_invoice'],
            LimitedEntity::EXPENSE => ['can_create_expense', 'can_update_expense'],
            LimitedEntity::BUSINESS => ['can_create_business', 'can_update_business'],
            LimitedEntity::CONTACT => ['can_create_contact', 'can_update_contact'],
            LimitedEntity::PRODUCT => ['can_create_product', 'can_update_product'],
            null => [],
        };
        $user = backpack_user();

        return $user?->canAny($permissions) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entity' => ['required', Rule::enum(LimitedEntity::class)],
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'entity' => __('role_limits.usage.title'),
            'user_id' => __('parties.fields.owner'),
        ];
    }
}

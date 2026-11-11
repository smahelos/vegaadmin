<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
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
            LimitedEntity::INVOICE => [Permission::INVOICE_CREATE, Permission::INVOICE_UPDATE],
            LimitedEntity::EXPENSE => [Permission::EXPENSE_CREATE, Permission::EXPENSE_UPDATE],
            LimitedEntity::BUSINESS => [Permission::BUSINESS_CREATE, Permission::BUSINESS_UPDATE],
            LimitedEntity::CONTACT => [Permission::CONTACT_CREATE, Permission::CONTACT_UPDATE],
            LimitedEntity::PRODUCT => [Permission::PRODUCT_CREATE, Permission::PRODUCT_UPDATE],
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

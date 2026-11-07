<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\User;

use App\Domain\User\Enums\LimitedEntity;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\RoleLimit;
use Spatie\Permission\Models\Role;

trait RoleLimitFormFields
{
    /**
     * The role is chosen on create only, from the web roles that have no limits yet.
     *
     * @return array<string, mixed>
     */
    protected function getRoleLimitRoleField(): array
    {
        $roleQuery = Role::where('guard_name', PermissionCatalog::WEB);
        $roleQuery->whereNotIn(
            'id',
            RoleLimit::select('role_id'),
        );
        $roleQuery->orderBy('name');
        $roles = $roleQuery->pluck('name', 'id');

        return [
            'name' => 'role_id',
            'label' => __('role_limits.fields.role'),
            'type' => 'select_from_array',
            'options' => $roles->all(),
            'allows_null' => false,
            'hint' => __('role_limits.hints.role'),
        ];
    }

    /**
     * The role of a stored row is only shown: it is not an input, so an update cannot move limits to another role.
     *
     * @return array<string, mixed>
     */
    protected function getRoleLimitRoleNameField(RoleLimit $roleLimit): array
    {
        $role = $roleLimit->role;
        $label = __('role_limits.fields.role');
        $label = e($label);
        $name = e($role->name);

        return [
            'name' => 'role_name',
            'type' => 'custom_html',
            'value' => '<label class="form-label">' . $label . '</label><p class="mb-0">' . $name . '</p>',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getRoleLimitFields(): array
    {
        $fields = [];
        foreach (LimitedEntity::cases() as $entity) {
            $hint = 'role_limits.hints.capacity';
            if ($entity->isMonthlyQuota()) {
                $hint = 'role_limits.hints.quota';
            }

            $fields[] = [
                'name' => $entity->value,
                'label' => __('role_limits.fields.' . $entity->value),
                'type' => 'role_limit',
                'hint' => __($hint),
            ];
        }

        return $fields;
    }
}

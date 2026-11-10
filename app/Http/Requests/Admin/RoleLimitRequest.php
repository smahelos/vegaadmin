<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\LimitMode;
use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Each limited entity comes as `<column>_mode` and `<column>`; the number is read only for a limited mode and the
 * pair becomes the column value (NULL unlimited, 0 not allowed) once validated. The role is chosen on create only.
 */
final class RoleLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();
        if ($this->isMethod('POST')) {
            return $user?->can(Permission::ROLE_LIMIT_CREATE) === true;
        }

        return $user?->can(Permission::ROLE_LIMIT_UPDATE) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [];
        if ($this->isMethod('POST')) {
            $webRole = Rule::exists('roles', 'id');
            $webRole->where('guard_name', PermissionCatalog::WEB);
            $rules['role_id'] = [
                'required',
                'integer',
                $webRole,
                Rule::unique('role_limits', 'role_id'),
            ];
        }

        foreach (LimitedEntity::cases() as $entity) {
            $rules[$entity->value . '_mode'] = [
                'required',
                Rule::enum(LimitMode::class),
            ];
            // The columns are unsigned INT
            $rules[$entity->value] = [
                Rule::excludeIf(
                    $this->mode($entity) !== LimitMode::LIMITED,
                ),
                'required',
                'integer',
                'min:1',
                'max:4294967295',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['role_id' => __('role_limits.fields.role')];
        foreach (LimitedEntity::cases() as $entity) {
            $label = __('role_limits.fields.' . $entity->value);
            $attributes[$entity->value . '_mode'] = $label;
            $attributes[$entity->value] = $label;
        }

        return $attributes;
    }

    protected function passedValidation(): void
    {
        $limits = [];
        foreach (LimitedEntity::cases() as $entity) {
            $limits[$entity->value] = match ($this->mode($entity)) {
                LimitMode::LIMITED => $this->integer($entity->value),
                LimitMode::NOT_ALLOWED => 0,
                default => null,
            };
        }

        $this->merge($limits);
    }

    private function mode(LimitedEntity $entity): ?LimitMode
    {
        return $this->enum($entity->value . '_mode', LimitMode::class);
    }
}

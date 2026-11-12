<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\DTO\UserWriteData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin's user form, for creating and updating; an empty password on update keeps the stored one.
 */
final class UserRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');
        $emailUnique = Rule::unique('users', 'email');
        $emailUnique->ignore($id);

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                $emailUnique,
            ],
            'password' => [
                $id === null ? 'required' : 'nullable',
                'string',
                'confirmed',
            ],
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
            'password' => __('users.fields.password'),
            'roles' => __('users.fields.roles'),
            'permissions' => __('users.fields.permissions'),
        ];
    }

    public function writeData(): UserWriteData
    {
        $password = $this->validated('password');

        return new UserWriteData(
            (string) $this->string('name'),
            (string) $this->string('email'),
            is_string($password) ? $password : null,
        );
    }

    /** @return list<int> */
    public function roleIds(): array
    {
        return $this->ids('roles');
    }

    /** @return list<int> */
    public function permissionIds(): array
    {
        return $this->ids('permissions');
    }

    /** @return list<int> */
    private function ids(string $key): array
    {
        $ids = $this->validated($key) ?? [];

        return array_values(
            array_map(
                intval(...),
                $ids,
            ),
        );
    }
}

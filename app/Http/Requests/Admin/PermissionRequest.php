<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A permission saved on the PermissionManager screen. Each guard holds its own permission of a name, so the name is
 * unique within the guard only; the package's requests check it across guards on create and not at all on update.
 */
final class PermissionRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $nameUnique = Rule::unique('permissions', 'name');
        $nameUnique->where('guard_name', $this->guardName());
        $id = $this->route('id');
        if ($id !== null) {
            $nameUnique->ignore($id);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameUnique,
            ],
            'guard_name' => [
                'required',
                'string',
                Rule::in(
                    array_keys(
                        config('auth.guards'),
                    ),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('permissions.fields.name'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __(
                'permissions.validation.name_taken',
                ['guard' => $this->guardName()],
            ),
        ];
    }

    private function guardName(): string
    {
        $guard = $this->input('guard_name');

        return is_string($guard) ? $guard : '';
    }
}

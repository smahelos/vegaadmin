<?php

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class TaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();
        if ($this->isMethod('POST')) {
            return $user?->can(Permission::TAX_CREATE) === true;
        }

        return $user?->can(Permission::TAX_UPDATE) === true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ];
    }

    /**
     * Attribute translations
     */
    public function attributes(): array
    {
        return [
            'name' => __('taxes.fields.name'),
            'rate' => __('taxes.fields.rate'),
        ];
    }
}

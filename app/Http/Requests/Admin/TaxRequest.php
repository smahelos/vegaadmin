<?php

namespace App\Http\Requests\Admin;

class TaxRequest extends BaseEntityRequest
{
    /**
     * Entity type for limit checks
     */
    protected function getEntityType(): string
    {
        return 'tax';
    }

    /**
     * Required permission name
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_tax';
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

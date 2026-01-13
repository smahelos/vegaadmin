<?php

namespace App\Http\Requests;

/**
 * Frontend Tax Request
 * Mirrors admin validation while using web guard & frontend permission namespace.
 */
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
     * Required permission for frontend tax operations
     */
    protected function getRequiredPermission(): string
    {
        return 'frontend.can_create_edit_tax';
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
            'name' => __('tax.name'),
            'rate' => __('tax.rate'),
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => __('tax.name_required'),
            'rate.required' => __('tax.rate_required'),
            'rate.numeric' => __('tax.rate_numeric'),
            'rate.min' => __('tax.rate_min'),
        ];
    }
}

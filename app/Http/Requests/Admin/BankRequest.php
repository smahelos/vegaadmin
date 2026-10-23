<?php

namespace App\Http\Requests\Admin;

/**
 * Admin Bank Request
 *
 * Extends BaseEntityRequest to enforce permissions and entity limits
 * Permission: can_create_edit_bank
 * Entity type (limits): bank
 */
class BankRequest extends BaseEntityRequest
{
    /**
     * Get required permission for bank operations.
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_bank';
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        $id = $this->route('id') ?: $this->id; // Support PUT/PATCH route parameter id

        $rules = [
            'name' => 'required|string|min:2|max:255',
            'code' => 'required|string|min:2|max:10|unique:banks,code,' . $id,
            'swift' => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
            'active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ];

        // For updates make required fields sometimes (handled similarly as other requests)
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|string|min:2|max:255';
            $rules['code'] = 'sometimes|string|min:2|max:10|unique:banks,code,' . $id;
            $rules['country'] = 'sometimes|string|size:2';
        }

        return $rules;
    }

    /**
     * Custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('banks.fields.name'),
            'code' => __('banks.fields.code'),
            'swift' => __('banks.fields.swift'),
            'country' => __('banks.fields.country'),
            'active' => __('banks.fields.active'),
            'description' => __('banks.fields.description'),
        ];
    }

    /**
     * Normalize input data before validation.
     */
    public function prepareForValidation(): void
    {
        if ($this->has('country') && is_string($this->country)) {
            $this->merge([
                'country' => strtoupper($this->country),
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArchivePolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated backpack users with system configuration permission
        return backpack_auth()->check() && backpack_auth()->user()->can('can_configure_system');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'table_name' => 'required|string|min:2|max:255',
            'retention_months' => 'required|integer|min:1|max:120',
            'date_column' => 'required|string|min:2|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'table_name' => __('admin/database.archive_policies.fields.table_name'),
            'retention_months' => __('admin/database.archive_policies.fields.retention_months'),
            'date_column' => __('admin/database.archive_policies.fields.date_column'),
            'is_active' => __('admin/database.archive_policies.fields.is_active'),
            'description' => __('admin/database.archive_policies.fields.description'),
        ];
    }
}

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
            'table_name' => __('admin.database.table_name'),
            'retention_months' => __('admin.database.retention_months'),
            'date_column' => __('admin.database.date_column'),
            'is_active' => __('admin.database.is_active'),
            'description' => __('admin.database.description'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'table_name.required' => __('admin.database.table_name_required'),
            'table_name.string' => __('validation.string', ['attribute' => __('admin.database.table_name')]),
            'table_name.min' => __('validation.min.string', ['attribute' => __('admin.database.table_name'), 'min' => 2]),
            'table_name.max' => __('validation.max.string', ['attribute' => __('admin.database.table_name'), 'max' => 255]),
            'retention_months.required' => __('admin.database.retention_months_required'),
            'retention_months.integer' => __('validation.integer', ['attribute' => __('admin.database.retention_months')]),
            'retention_months.min' => __('admin.database.retention_months_min'),
            'retention_months.max' => __('admin.database.retention_months_max'),
            'date_column.required' => __('admin.database.date_column_required'),
            'date_column.string' => __('validation.string', ['attribute' => __('admin.database.date_column')]),
            'date_column.min' => __('validation.min.string', ['attribute' => __('admin.database.date_column'), 'min' => 2]),
            'date_column.max' => __('validation.max.string', ['attribute' => __('admin.database.date_column'), 'max' => 255]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => __('admin.database.is_active')]),
            'description.string' => __('validation.string', ['attribute' => __('admin.database.description')]),
            'description.max' => __('validation.max.string', ['attribute' => __('admin.database.description'), 'max' => 1000]),
        ];
    }
}

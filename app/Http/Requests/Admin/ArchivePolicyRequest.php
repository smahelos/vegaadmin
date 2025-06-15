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
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'table_name' => 'required|string|max:255',
            'retention_months' => 'required|integer|min:1|max:120',
            'date_column' => 'required|string|max:255',
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
            'retention_months.required' => __('admin.database.retention_months_required'),
            'retention_months.min' => __('admin.database.retention_months_min'),
            'retention_months.max' => __('admin.database.retention_months_max'),
            'date_column.required' => __('admin.database.date_column_required'),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage expenses
        return backpack_user() && backpack_user()->can('can_create_edit_expense');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        // Determine current ID (if updating) using route parameter; fall back to NULL for create.
        $id = $this->route('id');
        if (!$id && $this->has('id')) {
            $id = $this->get('id');
        }
        $id = $id ?: 'NULL';

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:expense_categories,slug,' . $id,
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.expenses.name'),
            'slug' => trans('admin.expenses.slug'),
            'color' => trans('admin.expenses.color'),
            'description' => trans('admin.expenses.description'),
            'is_active' => trans('admin.expenses.is_active'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * Mirrors frontend pattern using generic Laravel validation lines with translated attributes.
     *
     * Only rules that differ from default or where explicit attribute substitution is desired are specified.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => trans('admin.expenses.name')]),
            'name.min' => __('validation.min.string', ['attribute' => trans('admin.expenses.name'), 'min' => 2]),
            'name.max' => __('validation.max.string', ['attribute' => trans('admin.expenses.name'), 'max' => 255]),
            'slug.required' => __('validation.required', ['attribute' => trans('admin.expenses.slug')]),
            'slug.min' => __('validation.min.string', ['attribute' => trans('admin.expenses.slug'), 'min' => 2]),
            'slug.max' => __('validation.max.string', ['attribute' => trans('admin.expenses.slug'), 'max' => 255]),
            'slug.unique' => __('validation.unique', ['attribute' => trans('admin.expenses.slug')]),
            'color.max' => __('validation.max.string', ['attribute' => trans('admin.expenses.color'), 'max' => 50]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => trans('admin.expenses.is_active')]),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

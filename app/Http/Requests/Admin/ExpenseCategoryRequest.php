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
     * @return array
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? 'NULL';
        
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'required|min:2|max:255|unique:expense_categories,slug,'.$id,
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
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

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->can('can_create_edit_expense');
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
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.validation.required', ['field' => trans('admin.expenses.name')]),
            'name.min' => trans('admin.validation.min', ['field' => trans('admin.expenses.name'), 'min' => 2]),
            'name.max' => trans('admin.validation.max', ['field' => trans('admin.expenses.name'), 'max' => 255]),
            'slug.required' => trans('admin.validation.required', ['field' => trans('admin.expenses.slug')]),
            'slug.min' => trans('admin.validation.min', ['field' => trans('admin.expenses.slug'), 'min' => 2]),
            'slug.max' => trans('admin.validation.max', ['field' => trans('admin.expenses.slug'), 'max' => 255]),
            'slug.unique' => trans('admin.validation.unique', ['field' => trans('admin.expenses.slug')]),
            'color.max' => trans('admin.validation.max', ['field' => trans('admin.expenses.color'), 'max' => 50]),
            'is_active.boolean' => trans('admin.validation.boolean', ['field' => trans('admin.expenses.is_active')]),
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

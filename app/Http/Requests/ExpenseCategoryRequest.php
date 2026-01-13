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

        return $user->can('frontend.can_create_edit_expense');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
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
     * Get the error messages for the defined validation rules.
     * Only custom messages that differ from default Laravel messages are defined here.
     * Other validation rules will fall back to the default Laravel translation lines using attributes().
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
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

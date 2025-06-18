<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StatusCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage statuses
        return backpack_user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? 'NULL';
        
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'required|min:2|max:255|unique:status_categories,slug,'.$id,
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.status_categories.name'),
            'slug' => trans('admin.status_categories.slug'),
            'description' => trans('admin.status_categories.description'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.status_categories.validation.name_required'),
            'name.min' => trans('admin.status_categories.validation.name_min'),
            'name.max' => trans('admin.status_categories.validation.name_max'),
            'slug.required' => trans('admin.status_categories.validation.slug_required'),
            'slug.min' => trans('admin.status_categories.validation.slug_min'),
            'slug.max' => trans('admin.status_categories.validation.slug_max'),
            'slug.unique' => trans('admin.status_categories.validation.slug_unique'),
            'description.string' => trans('admin.status_categories.validation.description_string'),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StatusCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorize only if authenticated backpack user has permission
        return backpack_auth()->check() && backpack_auth()->user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Determine current model id for update scenario
        $id = $this->get('id') ?? $this->route('id') ?? $this->route('status_category');

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:status_categories,slug,' . ($id ?? 'NULL'),
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
            'name.string' => trans('admin.status_categories.validation.name_string'),
            'name.min' => trans('admin.status_categories.validation.name_min'),
            'name.max' => trans('admin.status_categories.validation.name_max'),
            'slug.required' => trans('admin.status_categories.validation.slug_required'),
            'slug.string' => trans('admin.status_categories.validation.slug_string'),
            'slug.min' => trans('admin.status_categories.validation.slug_min'),
            'slug.max' => trans('admin.status_categories.validation.slug_max'),
            'slug.unique' => trans('admin.status_categories.validation.slug_unique'),
            'description.string' => trans('admin.status_categories.validation.description_string'),
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
        if (empty($this->slug) && !empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

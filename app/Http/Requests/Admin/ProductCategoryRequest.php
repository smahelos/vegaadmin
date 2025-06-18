<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow logged in users to create and update product categories
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|max:255|unique:product_categories,slug,' . $this->id,
            'description' => 'nullable',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.name'),
            'slug' => trans('admin.slug'),
            'description' => trans('admin.description'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.product_categories.validation.name_required'),
            'name.min' => trans('admin.product_categories.validation.name_min'),
            'name.max' => trans('admin.product_categories.validation.name_max'),
            'slug.max' => trans('admin.product_categories.validation.slug_max'),
            'slug.unique' => trans('admin.product_categories.validation.slug_unique'),
        ];
    }
}

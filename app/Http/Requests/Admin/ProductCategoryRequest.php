<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;

class ProductCategoryRequest extends BaseEntityRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Entity type for limit checks
     */
    protected function getEntityType(): string
    {
        return 'product_category';
    }

    /**
     * Required permission name
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_product'; // same permission group for products & categories
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,' . $this->id,
            'description' => 'nullable|string',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('id') ?: $this->id;
            $rules['slug'] = 'sometimes|string|max:255|unique:product_categories,slug,' . $id;
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.product_categories.name'),
            'slug' => trans('admin.product_categories.slug'),
            'description' => trans('admin.product_categories.description'),
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

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

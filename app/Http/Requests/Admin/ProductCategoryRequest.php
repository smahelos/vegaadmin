<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductCategoryRequest extends FormRequest
{
    /**
     * Categories are managed with the product permissions
     */
    public function authorize(): bool
    {
        $user = backpack_user();
        if ($this->isMethod('POST')) {
            return $user?->can('can_create_product') === true;
        }

        return $user?->can('can_update_product') === true;
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
            'name' => __('products.categories.fields.name'),
            'slug' => __('products.categories.fields.slug'),
            'description' => __('products.categories.fields.description'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge(
                [
                    'slug' => Str::slug($this->name),
                ],
            );
        }
    }
}

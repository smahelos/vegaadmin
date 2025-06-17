<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow logged in users to create and update products
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,' . $this->id,
            'user_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable',
            'is_default' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.products.name'),
            'slug' => trans('admin.products.slug'),
            'description' => trans('admin.products.description'),
            'price' => trans('admin.products.price'),
            'tax_id' => trans('admin.products.tax'),
            'supplier_id' => trans('admin.products.supplier'),
            'category_id' => trans('admin.products.category'),
            'image' => trans('admin.products.image'),
            'is_default' => trans('admin.products.is_default'),
            'is_active' => trans('admin.products.is_active'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.products.validation.name_required'),
            'name.min' => trans('admin.products.validation.name_min'),
            'name.max' => trans('admin.products.validation.name_max'),
            'price.required' => trans('admin.products.validation.price_required'),
            'price.numeric' => trans('admin.products.validation.price_numeric'),
            'price.min' => trans('admin.products.validation.price_min'),
            'user_id.required' => trans('admin.products.validation.user_required'),
            'user_id.exists' => trans('admin.products.validation.user_exists'),
            'tax_id.exists' => trans('admin.products.validation.tax_exists'),
            'supplier_id.exists' => trans('admin.products.validation.supplier_exists'),
            'category_id.exists' => trans('admin.products.validation.category_exists'),
            'image.image' => trans('admin.products.validation.image_format'),
            'image.max' => trans('admin.products.validation.image_size'),
        ];
    }
}

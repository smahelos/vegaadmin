<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Generate slug from name if not provided
        if (!$this->filled('slug') && $this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get product ID from route parameter
        $id = $this->route('id');
        
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,' . $id,
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'image' => $id ? 'nullable|image|max:2048' : 'nullable|image|max:2048',
            'currency' => 'required|string|in:CZK,EUR,USD',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('products.fields.name'),
            'slug' => trans('products.fields.slug'),
            'price' => trans('products.fields.price'),
            'tax_id' => trans('products.fields.tax_id'),
            'category_id' => trans('products.fields.category_id'),
            'description' => trans('products.fields.description'),
            'is_default' => trans('products.fields.is_default'),
            'is_active' => trans('products.fields.is_active'),
            'image' => trans('products.fields.image'),
            'currency' => trans('products.fields.currency'),
        ];
    }
}

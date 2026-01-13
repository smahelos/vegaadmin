<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

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
     */
    protected function prepareForValidation(): void
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
        $id = $this->route('id') ?: $this->id;

        // Get max file size from config
        $maxFileSize = \Illuminate\Support\Facades\Config::get('file_upload.contexts.product_image.max_kb', 2048);

        $rules = [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $id,
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:' . $maxFileSize,
            'currency' => 'required|string|in:CZK,EUR,USD',
        ];

        // For updates make slug optional (sometimes) but still unique ignoring current product id
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = 'sometimes|string|max:255|unique:products,slug,' . $id;
        }

        return $rules;
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
            'supplier_id' => trans('products.fields.supplier_id'),
            'description' => trans('products.fields.description'),
            'is_default' => trans('products.fields.is_default'),
            'is_active' => trans('products.fields.is_active'),
            'image' => trans('products.fields.image'),
            'currency' => trans('products.fields.currency'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator,
            redirect()->back()
                ->withErrors($validator->errors())
                ->withInput()
                ->with('error', trans('products.messages.validation_failed'))
        );
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('products.validation.name_required'),
            'name.min' => trans('products.validation.name_min'),
            'name.max' => trans('products.validation.name_max'),
            'slug.unique' => trans('products.validation.slug_unique'),
            'price.equired' => trans('products.validation.price_required'),
            'price.numeric' => trans('products.validation.price_numeric'),
            'price.min' => trans('products.validation.price_min'),
            'currency.required' => trans('products.validation.currency_required'),
            'currency.in' => trans('products.validation.currency_in'),
            'image.image' => trans('products.validation.image_image'),
            'image.max' => trans('products.validation.image_size'),
        ];
    }
}

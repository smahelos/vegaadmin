<?php

namespace App\Http\Requests;

use App\Http\Requests\Rules\UniquePerUser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
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
        if (! $this->filled('slug') && $this->filled('name')) {
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
        $maxFileSize = Config::get('file_upload.contexts.product_image.max_kb', 2048);

        $rules = [
            'name' => 'required|string|min:2|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                UniquePerUser::rule(
                    'products',
                    'slug',
                    $this->user()?->id,
                    $id,
                ),
            ],
            'price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:' . $maxFileSize,
            'currency' => 'required|string|in:CZK,EUR,USD',
        ];

        // For updates make slug optional (sometimes) but still unique ignoring current product id
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = [
                'sometimes',
                'string',
                'max:255',
                UniquePerUser::rule(
                    'products',
                    'slug',
                    $this->user()?->id,
                    $id,
                ),
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('products.fields.name'),
            'slug' => __('products.fields.slug'),
            'price' => __('products.fields.price'),
            'tax_id' => __('products.fields.tax_id'),
            'category_id' => __('products.fields.category_id'),
            'description' => __('products.fields.description'),
            'is_default' => __('products.fields.is_default'),
            'is_active' => __('products.fields.is_active'),
            'image' => __('products.fields.image'),
            'currency' => __('products.fields.currency'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator,
            redirect()->back()
                ->withErrors($validator->errors())
                ->withInput()
                ->with('error', trans('products.messages.validation_failed')),
        );
    }
}

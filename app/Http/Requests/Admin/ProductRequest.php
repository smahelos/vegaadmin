<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Shared\Money\Enums\Currency;
use App\Http\Requests\Rules\UniquePerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                UniquePerUser::rule(
                    'products',
                    'slug',
                    $this->input('user_id'),
                    $this->id,
                ),
            ],
            'user_id' => 'required|exists:users,id',
            'price' => 'required|numeric|gt:0|max:999999.99',
            'currency' => ['required', Rule::enum(Currency::class)],
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ];

        // For updates, slug becomes sometimes (optional) but must remain unique in products table ignoring current id
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('id') ?: $this->id;
            $rules['slug'] = [
                'sometimes',
                'string',
                'max:255',
                UniquePerUser::rule(
                    'products',
                    'slug',
                    $this->input('user_id'),
                    $id,
                ),
            ];
        }

        return $rules;
    }

    public function toWriteData(): ProductWriteData
    {
        return ProductWriteData::fromArray($this->validated());
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('products.fields.name'),
            'slug' => __('products.fields.slug'),
            'user_id' => __('products.fields.user_id'),
            'price' => __('products.fields.price'),
            'currency' => __('products.fields.currency'),
            'tax_id' => __('products.fields.tax_id'),
            'category_id' => __('products.fields.category_id'),
            'description' => __('products.fields.description'),
            'is_default' => __('products.fields.is_default'),
            'is_active' => __('products.fields.is_active'),
            'image' => __('products.fields.image'),
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
                    'slug' => Str::slug(
                        (string) $this->input('name'),
                    ),
                ],
            );
        }
    }
}

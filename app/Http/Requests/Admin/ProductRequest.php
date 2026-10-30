<?php

namespace App\Http\Requests\Admin;

use App\Domain\Shared\Money\Enums\Currency;
use App\Http\Requests\Rules\UniquePerUser;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends BaseEntityRequest
{
    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'product';
    }

    /**
     * Get required permission for product operations
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_product';
    }

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
            'price' => 'required|numeric|min:0',
            'currency' => ['required', Rule::enum(Currency::class)],
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable',
            'is_default' => 'boolean',
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
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Domain\Shared\Money\Enums\Currency;
use App\Http\Requests\Rules\UniquePerUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * The product fields the frontend and admin requests share: one rule set, the admin request adds the owner.
 */
final class ProductInput
{
    /**
     * @return array<string, string|list<string|object>>
     */
    public static function rules(
        int|string|null $ownerId,
        int|string|null $productId,
        bool $updating,
    ): array {
        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => [
                $updating ? 'sometimes' : 'nullable',
                'string',
                'max:255',
                UniquePerUser::rule(
                    'products',
                    'slug',
                    $ownerId,
                    $productId,
                ),
            ],
            'price' => 'required|numeric|gt:0|max:999999.99',
            'currency' => ['required', Rule::enum(Currency::class)],
            'tax_id' => 'nullable|exists:taxes,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:' . Config::get('file_upload.contexts.product_image.max_kb', 2048),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        $attributes = [];
        foreach (
            [
                'name',
                'slug',
                'price',
                'currency',
                'tax_id',
                'category_id',
                'description',
                'is_default',
                'is_active',
                'image',
            ] as $field
        ) {
            $attributes[$field] = __('products.fields.' . $field);
        }

        return $attributes;
    }
}

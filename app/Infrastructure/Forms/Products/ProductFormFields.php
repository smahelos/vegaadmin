<?php

namespace App\Infrastructure\Forms\Products;

use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;
use App\Models\ProductCategory;
use App\Models\Tax;
use Illuminate\Support\Facades\App;

trait ProductFormFields
{
    /**
     * Get product form field definitions
     */
    protected function getProductFields(array $productCategories = [], array $taxRates = []): array
    {
        // Get currency codes from CurrencyService
        $currencies = App::make(CurrencyServiceInterface::class)->getAllCurrencies();

        // Default currencies if not provided
        if (empty($currencies)) {
            $currencies = [
                'CZK' => 'CZK',
                'EUR' => 'EUR',
                'USD' => 'USD',
            ];
        }

        return [
            [
                'name' => 'name',
                'label' => __('products.fields.name'),
                'type' => 'text',
                'hint' => __('products.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'slug',
                'label' => __('products.fields.slug'),
                'type' => 'text',
                'hint' => __('products.hints.slug'),
                'required' => true,
            ],
            [
                'name' => 'category_id',
                'label' => __('products.fields.category_id'),
                'type' => 'select',
                'options' => $productCategories,
                'entity' => 'paymentStatus',
                'attribute' => 'name',
                'hint' => __('products.hints.category_id'),
                'model' => ProductCategory::class,
                'placeholder' => __('products.placeholders.select_category'),
                'required' => true,
            ],
            [
                'name' => 'tax_id',
                'label' => __('products.fields.tax_id'),
                'type' => 'select',
                'options' => $taxRates,
                'entity' => 'paymentStatus',
                'attribute' => 'name',
                'hint' => __('products.hints.tax_id'),
                'model' => Tax::class,
                'placeholder' => __('products.placeholders.select_tax'),
                'required' => true,
            ],
            [
                'name' => 'price',
                'label' => __('products.fields.price'),
                'type' => 'number',
                'default' => 0,
                'hint' => __('products.hints.price'),
                'required' => true,
            ],
            [
                'name' => 'currency',
                'label' => __('products.fields.currency'),
                'type' => 'select_from_array',
                'options' => $currencies,
                'hint' => __('products.hints.currency'),
                'placeholder' => __('products.placeholders.select_currency'),
                'required' => true,
            ],
            [
                'name' => 'description',
                'label' => __('products.fields.description'),
                'type' => 'textarea',
                'hint' => __('products.hints.description'),
                'required' => false,
            ],
            [
                'name' => 'image',
                'label' => __('products.fields.image'),
                'type' => 'file',
                'hint' => __('products.hints.image'),
                'required' => false,
            ],
            [
                'name' => 'is_default',
                'label' => __('products.fields.is_default'),
                'type' => 'checkbox',
                'hint' => __('products.hints.is_default'),
                'required' => false,
            ],
            [
                'name' => 'is_active',
                'label' => __('products.fields.is_active'),
                'type' => 'checkbox',
                'hint' => __('products.hints.is_active'),
                'required' => false,
            ],
        ];
    }
}

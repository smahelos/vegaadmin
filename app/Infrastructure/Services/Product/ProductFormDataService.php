<?php

namespace App\Infrastructure\Services\Product;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface;
use App\Models\ProductCategory;
use App\Models\Tax;

/**
 * Product-specific form data service.
 * Handles categories and tax rates for Product forms.
 */
class ProductFormDataService implements ProductFormDataServiceInterface
{
    /**
     * Cache TTL for form data (6 hours)
     */
    private const FORM_DATA_CACHE_TTL = 21600;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {}

    public function getFormData(): array
    {
        $cacheKey = $this->cacheService->globalKey('product_form_data');

        return $this->cacheService->remember(
            $cacheKey,
            function () {
                // Get product categories for dropdown
                $productCategories = ProductCategory::pluck('slug', 'id');
                $productCategories = $productCategories->toArray();

                // Get tax rates for dropdown
                $query = Tax::where('slug', 'dph');
                $taxRates = $query->pluck('rate', 'id');
                $taxRates = $taxRates->toArray();

                // Format tax rates with percentage sign
                foreach ($taxRates as $key => $value) {
                    $taxRates[$key] = $value . '%';
                }

                return [
                    'product_categories' => $productCategories,
                    'tax_rates' => $taxRates,
                    'categories' => ProductCategory::all(),
                ];
            },
            self::FORM_DATA_CACHE_TTL,
            ['form_data', 'products'],
        );
    }

    public function getAllCategories(): array
    {
        $cacheKey = 'product_categories';

        return $this->cacheService->remember(
            $cacheKey,
            function () {
                $query = ProductCategory::query();

                $query->orderBy('name');
                $categories = $query->get();

                $result = [];
                foreach ($categories as $category) {
                    $result[$category->id] = $category->name;
                }

                return $result;
            },
            60 * 5,
            ['form_data', 'products'],
        );
    }
}

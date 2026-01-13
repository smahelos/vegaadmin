<?php

namespace App\Infrastructure\Services\Product;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Tax;
use Illuminate\Support\Facades\Auth;

/**
 * Product-specific form data service.
 * Handles categories, suppliers, tax rates for Product forms.
 */
class ProductFormDataService implements ProductFormDataServiceInterface
{
    /**
     * Cache TTL for form data (6 hours)
     */
    private const FORM_DATA_CACHE_TTL = 21600;

    public function __construct(
        private readonly CacheServiceInterface $cacheService
    ) {
    }

    public function getFormData(): array
    {
        $cacheKey = $this->cacheService->globalKey('product_form_data');
        
        return $this->cacheService->remember(
            $cacheKey,
            function () {
                // Get product categories for dropdown
                $productCategories = ProductCategory::pluck('slug', 'id')->toArray();

                // Get tax rates for dropdown
                $taxRates = Tax::where('slug', 'dph')
                    ->pluck('rate', 'id')
                    ->toArray();

                // Format tax rates with percentage sign
                foreach($taxRates as $key => $value) {
                    $taxRates[$key] = $value . '%';
                }

                return [
                    'product_categories' => $productCategories,
                    'tax_rates' => $taxRates,
                    'categories' => ProductCategory::all(),
                ];
            },
            self::FORM_DATA_CACHE_TTL,
            ['form_data', 'products']
        );
    }

    public function getAllCategories(): array
    {
        $cacheKey = 'product_categories';

        return $this->cacheService->remember($cacheKey, function () {
            $query = ProductCategory::query();
            
            $categories = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($categories as $category) {
                $result[$category->id] = $category->name;
            }
            
            return $result;
        }, 60 * 5, ['form_data', 'products']);
    }

    public function getAllSuppliers(): array
    {
        $cacheKey = 'product_suppliers';

        return $this->cacheService->remember($cacheKey, function () {
            $query = Supplier::query();
            $query->where('user_id', Auth::id());

            $suppliers = $query->orderBy('name')->get();

            $result = [];
            foreach ($suppliers as $supplier) {
                $result[$supplier->id] = $supplier->name;
            }
            
            return $result;
        }, 60 * 5, ['form_data', 'products']);
    }

    public function getAllSuppliersForUser(int $userId): array
    {
        $cacheKey = "product_suppliers:{$userId}";

        return $this->cacheService->remember($cacheKey, function () use ($userId) {
            $query = Supplier::query();
            $query->where('user_id', $userId);

            $suppliers = $query->orderBy('name')->get();

            $result = [];
            foreach ($suppliers as $supplier) {
                $result[$supplier->id] = $supplier->name;
            }
            
            return $result;
        }, 60 * 5, ['form_data', 'products']);
    }

    public function invalidateFormDataCache(): bool
    {
        return $this->cacheService->invalidateTags(['form_data', 'products']);
    }

    public function clearCategoriesCache(): void
    {   
        // Delete cache for all categories
        $categories = ProductCategory::pluck('slug')->toArray();
        foreach ($categories as $slug) {
            $this->cacheService->forget("products_by_category:{$slug}:0");
            $this->cacheService->forget("products_by_category:{$slug}:1");
        }
        
        $this->cacheService->forget('product_categories');
    }
}

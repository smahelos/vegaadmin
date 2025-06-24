<?php

namespace App\Services;

use App\Contracts\ProductServiceInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Contracts\CacheServiceInterface;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService implements ProductServiceInterface
{
    /**
     * Product repository instance
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Cache service instance
     *
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * Cache TTL for form data (6 hours)
     */
    private const FORM_DATA_CACHE_TTL = 21600;

    /**
     * Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CacheServiceInterface $cacheService
    ) {
        $this->productRepository = $productRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * Create new product with business logic
     *
     * @param array $data
     * @param User $user
     * @return Product
     */
    public function createProduct(array $data, User $user): Product
    {
        // Set user ID
        $data['user_id'] = $user->id;

        // Set as default if it's the first product
        if ($this->productRepository->getUserProductCount($user) === 0) {
            $data['is_default'] = true;
        }

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->productRepository->create($data);
    }

    /**
     * Update product with business logic
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        // Ensure boolean fields are properly set
        $data['is_default'] = isset($data['is_default']) && $data['is_default'] == 1;
        $data['is_active'] = isset($data['is_active']) && $data['is_active'] == 1;

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->productRepository->update($product, $data);
    }

    /**
     * Delete product with cleanup
     *
     * @param Product $product
     * @return bool
     */
    public function deleteProduct(Product $product): bool
    {
        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $this->productRepository->delete($product);
    }

    /**
     * Get form data for product creation/editing
     *
     * @return array
     */
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

    /**
     * Invalidate product form data cache
     *
     * @return bool
     */
    public function invalidateFormDataCache(): bool
    {
        return $this->cacheService->invalidateTags(['form_data', 'products']);
    }

    /**
     * Generate slug from name
     *
     * @param string $name
     * @return string
     */
    public function generateSlug(string $name): string
    {
        return Str::slug($name);
    }
}

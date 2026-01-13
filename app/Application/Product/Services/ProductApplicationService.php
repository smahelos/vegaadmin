<?php

namespace App\Application\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Application\Product\Mappers\ProductArrayMapper;
use App\Application\Product\Services\ProductAuthorizationService;
use App\Application\Product\Services\ProductInputValidationService;
use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Domain\Product\Contracts\ProductServiceInterface as ProductDomainService;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use Illuminate\Support\Collection;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Support\Facades\DB;

/**
 * Clean Application-layer service following PartyApplicationService pattern.
 *
 * Orchestrates Domain services (business logic) with Infrastructure services (caching, files, etc.).
 * This is the proper DDD approach - Application layer coordinates between layers.
 */
class ProductApplicationService implements ProductApplicationServiceInterface
{
    public function __construct(
        private readonly ProductDomainService $productDomainService,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAuthorizationService $authService,
        private readonly ProductArrayMapper $productMapper,
        private readonly ProductInputValidationService $validationService,
        private readonly CacheServiceInterface $cacheService,
        private readonly FileUploadServiceInterface $fileService,
        private readonly ProductFormDataServiceInterface $formDataService,
    ) {
    }

    public function getFormData(): array
    {
        return $this->formDataService->getFormData();
    }

    public function createProduct(array $data, int $userId): ProductDTO
    {
        $data['user_id'] = $userId;
        
        $this->validationService->validateProductBusinessRules($data, $userId);
        
        $writeData = ProductWriteData::fromArray($data);
        
        return DB::transaction(fn() => 
            $this->productDomainService->createProduct($writeData, UserId::fromInt($userId))
        );
    }

    public function updateProduct(int $productId, array $data, int $userId): ProductDTO
    {
        // Add user_id to data for validation
        $data['user_id'] = $userId;
        
        // Verify product exists and user has access (do not rely on global auth())
        $product = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));
        
        $this->validationService->validateProductBusinessRules($data, $userId, $productId);
        
        $writeData = ProductWriteData::fromArray($data);
        $id = ProductId::fromInt($productId);
        
        return DB::transaction(fn() => 
            $this->productDomainService->updateProduct($id, $writeData)
        );
    }

    public function deleteProduct(int $productId, int $userId): void
    {
        // Verify product exists and user has access
        $product = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));
        
        // Handle image cleanup before deletion
        if ($product->image) {
            $this->fileService->deleteFile($product->image, 'public');
        }
        
        $id = ProductId::fromInt($productId);
        
        DB::transaction(fn() => 
            $this->productDomainService->deleteProduct($id)
        );
    }

    public function findProduct(int $userId, int $id): ProductDTO
    {
        $productId = ProductId::fromInt($id);
        $userVO = UserId::fromInt($userId);

        $product = $this->authService->canAccessAnyProducts($userId)
            ? $this->productDomainService->findProductAny($productId)
            : $this->productDomainService->findProductForUser($productId, $userVO);

        if (!$product) {
            throw new ModelNotFoundException('Product not found');
        }
        
        return $product;
    }

    public function listProducts(int $userId): Collection
    {
        return $this->authService->canAccessAnyProducts($userId)
            ? $this->productRepository->allForAdmin()
            : $this->productRepository->allForUser($userId);
    }

    public function getProductsDropdown(int $userId): array
    {
        return $this->authService->canAccessAnyProducts($userId)
            ? $this->productRepository->getProductsForDropdown(0) // Admin sees all
            : $this->productRepository->getProductsForDropdown($userId);
    }

    public function getDefaultProduct(int $userId): ?ProductDTO
    {
        return $this->productDomainService->getDefaultProduct(UserId::fromInt($userId));
    }

    public function getUserProductCount(int $userId): int
    {
        return $this->productDomainService->getUserProductCount(UserId::fromInt($userId));
    }

    /**
     * Check if user can view product (without loading Eloquent model).
     */
    public function authorizeViewProduct(int $userId, int $productId): void
    {
        $productDto = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));
        
        if ($productDto === null) {
            abort(404, 'Product not found');
        }
        
        if (!$this->authService->canViewProduct($userId, $productDto->user_id)) {
            abort(403, 'Unauthorized to view this product');
        }
    }

    /**
     * Check if user can update product (without loading Eloquent model).
     */
    public function authorizeUpdateProduct(int $userId, int $productId): void
    {
        $productDto = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));

        if (!$this->authService->canUpdateProduct($userId, $productDto->user_id)) {
            abort(403, 'Unauthorized to update this product');
        }
    }

    /**
     * Check if user can delete product (without loading Eloquent model).
     */
    public function authorizeDeleteProduct(int $userId, int $productId): void
    {
        $productDto = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));

        if ($productDto === null) {
            abort(404, 'Product not found');
        }

        if (!$this->authService->canDeleteProduct($userId, $productDto->user_id)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User cannot delete this product');
        }
    }

    public function handleProductImage(UploadedFile|string|null $image, ?string $oldImage = null): ?string
    {
        if (!$image) {
            return $oldImage;
        }
        // Convert UploadedFile to VO to decouple Domain contract from framework
        $value = $image instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($image) : $image;

        return $this->fileService->handleFileUpload(
            $value, 
            'image', 
            'products',
            [
                'createThumbnails' => true,
                'thumbnailWidth' => 200,
                'thumbnailHeight' => 200,
                'randomizeFilename' => true,
                'allowedFileTypes' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
            ],
            $oldImage
        );
    }

    /**
     * Generate slug from name
     */
    public function generateSlug(string $name): string
    {
        return $this->fileService->sanitizeFilename($name);
    }

    /**
     * Invalidate product form data cache
     */
    public function invalidateFormDataCache(): bool
    {
        return $this->formDataService->invalidateFormDataCache();
    }

    /**
     * Get all product categories
     */
    public function getAllCategories(): array
    {
        return $this->formDataService->getAllCategories();
    }

    /**
     * Get all suppliers for the current user
     */
    public function getAllSuppliers(): array
    {
        return $this->formDataService->getAllSuppliers();
    }

    /**
     * Get all suppliers for specific user (DTO version)
     */
    public function getAllSuppliersForUser(int $userId): array
    {
        return $this->formDataService->getAllSuppliersForUser($userId);
    }

    /**
     * Delete cache for all categories
     */
    public function clearCategoriesCache(): void
    {
        $this->formDataService->clearCategoriesCache();
    }
}

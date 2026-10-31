<?php

namespace App\Application\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Product\Contracts\ProductServiceInterface as ProductDomainService;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Application-layer service for products.
 *
 * Orchestrates Domain services (business logic) with Infrastructure services (caching, files, etc.).
 * This is the proper DDD approach - Application layer coordinates between layers.
 */
class ProductApplicationService implements ProductApplicationServiceInterface
{
    public function __construct(
        private readonly ProductDomainService $productDomainService,
        private readonly ProductAuthorizationService $authService,
        private readonly ProductInputValidationService $validationService,
        private readonly FileUploadServiceInterface $fileService,
        private readonly ProductFormDataServiceInterface $formDataService,
    ) {}

    public function getFormData(): array
    {
        return $this->formDataService->getFormData();
    }

    public function createProduct(array $data, int $userId): ProductDTO
    {
        $data['user_id'] = $userId;

        $this->validationService->validateProductBusinessRules($data, $userId);

        $writeData = ProductWriteData::fromArray($data);

        return DB::transaction(fn () => $this->productDomainService->createProduct($writeData, UserId::fromInt($userId)),
        );
    }

    public function updateProduct(
        int $productId,
        array $data,
        int $userId,
    ): ProductDTO {
        // Add user_id to data for validation
        $data['user_id'] = $userId;

        // Verify product exists and user has access (do not rely on global auth())
        $product = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));

        $this->validationService->validateProductBusinessRules(
            $data,
            $userId,
            $productId,
        );

        $writeData = ProductWriteData::fromArray($data);
        $id = ProductId::fromInt($productId);

        return DB::transaction(fn () => $this->productDomainService->updateProduct($id, $writeData),
        );
    }

    public function deleteProduct(int $productId, int $userId): void
    {
        // Verify product exists and user has access
        $product = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));

        $id = ProductId::fromInt($productId);

        DB::transaction(fn () => $this->productDomainService->deleteProduct($id),
        );

        // The file goes only once the product is gone, so a refused delete keeps the image
        if ($product->image) {
            $this->fileService->deleteFile($product->image, 'public');
        }
    }

    public function findProduct(int $userId, int $id): ProductDTO
    {
        $productId = ProductId::fromInt($id);
        $userVO = UserId::fromInt($userId);

        $product = $this->authService->canAccessAnyProducts($userId)
            ? $this->productDomainService->findProductAny($productId)
            : $this->productDomainService->findProductForUser($productId, $userVO);

        if (! $product) {
            throw new ModelNotFoundException('Product not found');
        }

        return $product;
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

        if (! $this->authService->canViewProduct($userId, $productDto->user_id)) {
            abort(403, 'Unauthorized to view this product');
        }
    }

    /**
     * Check if user can update product (without loading Eloquent model).
     */
    public function authorizeUpdateProduct(int $userId, int $productId): void
    {
        $productDto = $this->productDomainService->findProductForUser(ProductId::fromInt($productId), UserId::fromInt($userId));

        if (! $this->authService->canUpdateProduct($userId, $productDto->user_id)) {
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

        if (! $this->authService->canDeleteProduct($userId, $productDto->user_id)) {
            throw new AuthorizationException('User cannot delete this product');
        }
    }

    /**
     * Generate slug from name
     */
    public function generateSlug(string $name): string
    {
        return $this->fileService->sanitizeFilename($name);
    }

    /**
     * Get all product categories
     */
    public function getAllCategories(): array
    {
        return $this->formDataService->getAllCategories();
    }
}

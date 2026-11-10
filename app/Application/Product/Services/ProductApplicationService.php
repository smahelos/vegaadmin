<?php

namespace App\Application\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Product\Contracts\ProductServiceInterface as ProductDomainService;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Services\EntityLimit;
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
        private readonly UserPermissionServiceInterface $permissions,
        private readonly ProductInputValidationService $validationService,
        private readonly FileUploadServiceInterface $fileService,
        private readonly ProductFormDataServiceInterface $formDataService,
        private readonly EntityLimit $limits,
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
        $owner = UserId::fromInt($userId);

        return DB::transaction(
            function () use ($writeData, $owner): ProductDTO {
                $this->limits->assertCanCreate($owner, LimitedEntity::PRODUCT);

                return $this->productDomainService->createProduct($writeData, $owner);
            },
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
        $product = $this->productDomainService->findProductForUser(
            ProductId::fromInt($productId),
            UserId::fromInt($userId),
        );

        $this->validationService->validateProductBusinessRules(
            $data,
            $userId,
            $productId,
        );

        $writeData = ProductWriteData::fromArray($data);
        $id = ProductId::fromInt($productId);

        return DB::transaction(
            fn () => $this->productDomainService->updateProduct($id, $writeData),
        );
    }

    public function deleteProduct(int $productId, int $userId): void
    {
        // Verify product exists and user has access
        $product = $this->productDomainService->findProductForUser(
            ProductId::fromInt($productId),
            UserId::fromInt($userId),
        );

        $id = ProductId::fromInt($productId);

        DB::transaction(
            fn () => $this->productDomainService->deleteProduct($id),
        );

        // The file goes only once the product is gone, so a refused delete keeps the image
        if ($product->image) {
            $this->fileService->deleteFile($product->image, 'public');
        }
    }

    private function findProduct(int $userId, int $id): ProductDTO
    {
        $product = $this->productDomainService->findProductForUser(
            ProductId::fromInt($id),
            UserId::fromInt($userId),
        );

        if (!$product) {
            throw new ModelNotFoundException('Product not found');
        }

        return $product;
    }

    public function authorizeCreateProduct(int $userId): void
    {
        $this->requirePermission($userId, Permission::PRODUCT_CREATE);
    }

    public function authorizeViewProduct(int $userId, int $productId): ProductDTO
    {
        return $this->authorizeProduct(
            $userId,
            $productId,
            Permission::PRODUCT_VIEW,
        );
    }

    public function authorizeUpdateProduct(int $userId, int $productId): ProductDTO
    {
        return $this->authorizeProduct(
            $userId,
            $productId,
            Permission::PRODUCT_UPDATE,
        );
    }

    public function authorizeDeleteProduct(int $userId, int $productId): void
    {
        $this->authorizeProduct(
            $userId,
            $productId,
            Permission::PRODUCT_DELETE,
        );
    }

    /**
     * Another user's product is reported as not found, so its existence stays hidden.
     *
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    private function authorizeProduct(
        int $userId,
        int $productId,
        Permission $permission,
    ): ProductDTO {
        $product = $this->findProduct($userId, $productId);
        $this->requirePermission($userId, $permission);

        return $product;
    }

    /**
     * @throws AuthorizationException
     */
    private function requirePermission(int $userId, Permission $permission): void
    {
        if (
            !in_array(
                $permission->value,
                $this->permissions->getUserPermissions($userId, 'web'),
                true,
            )
        ) {
            throw new AuthorizationException('The user lacks the permission for this product');
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

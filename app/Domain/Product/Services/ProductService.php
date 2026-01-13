<?php

namespace App\Domain\Product\Services;

use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\Validation\ProductCreationValidator;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Product\Events\ProductCreated;

class ProductService implements ProductServiceInterface
{
    /**
     * Product read repository instance
     */
    private readonly ProductDtoReadRepositoryInterface $productReadRepository;

    /**
     * Product write repository instance
     */
    private readonly ProductDtoWriteRepositoryInterface $productWriteRepository;

    /**
     * Product validation service
     */
    private readonly ProductCreationValidator $validator;

    /**
     * Constructor
     */
    public function __construct(
        ProductDtoReadRepositoryInterface $productReadRepository,
        ProductDtoWriteRepositoryInterface $productWriteRepository,
        ProductCreationValidator $validator,
        private readonly EventPublisherInterface $events
    ) {
        $this->productReadRepository = $productReadRepository;
        $this->productWriteRepository = $productWriteRepository;
        $this->validator = $validator;
    }

    /**
     * Create new product with business logic using DTOs
     */
    public function createProduct(ProductWriteData $writeData, UserId $userId): ProductDTO
    {
        // Validate product data using domain validator
        $validatedData = $this->validator->validateForCreation($writeData->toArray(), $userId->toInt());
        
        // Get user product count
        if ($this->productReadRepository->getUserProductCount($userId) === 0) {
            // First product is automatically default
            $validatedData['is_default'] = true;
        }

        $validatedWriteData = ProductWriteData::fromArray($validatedData);

        $created = $this->productWriteRepository->create($validatedWriteData);

        // Publish Domain Event for new product creation via unified publisher
        $this->events->publish(new ProductCreated($created, $userId->toInt()));

        return $created;
    }

    /**
     * Update product with business logic using DTOs
     */
    public function updateProduct(ProductId $productId, ProductWriteData $writeData): ProductDTO
    {
        // Validate product data using domain validator
        $validatedData = $this->validator->validateForUpdate($writeData->toArray());
        
        $validatedWriteData = ProductWriteData::fromArray($validatedData);
        return $this->productWriteRepository->update($productId, $validatedWriteData);
    }

    /**
     * Delete product with cleanup using DTOs
     */
    public function deleteProduct(ProductId $productId): void
    {
        $this->productWriteRepository->delete($productId);
    }

    /**
     * Find product by ID for any user (admin access)
     */
    public function findProductAny(ProductId $productId): ?ProductDTO
    {
        return $this->productReadRepository->findByIdAny($productId);
    }

    /**
     * Find product by ID for specific user
     */
    public function findProductForUser(ProductId $productId, UserId $userId): ?ProductDTO
    {
        return $this->productReadRepository->findByIdForUser($productId, $userId);
    }

    /**
     * Get default product for user
     */
    public function getDefaultProduct(UserId $userId): ?ProductDTO
    {
        return $this->productReadRepository->getDefaultProduct($userId);
    }

    /**
     * Get user's product count
     */
    public function getUserProductCount(UserId $userId): int
    {
        return $this->productReadRepository->getUserProductCount($userId);
    }
}

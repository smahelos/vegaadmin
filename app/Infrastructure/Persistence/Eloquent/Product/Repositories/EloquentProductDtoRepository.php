<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentProductMapper;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductRepository;

/**
 * Eloquent implementation of Product DTO repository.
 * Delegates to existing EloquentProductRepository and maps results to DTOs.
 */
class EloquentProductDtoRepository implements ProductDtoReadRepositoryInterface, ProductDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentProductRepository $productRepository,
        private readonly EloquentProductMapper $mapper
    ) {
    }

    public function getDefaultProduct(UserId $userId): ?ProductDTO
    {
        $product = $this->productRepository->getDefaultProduct($userId->toInt());
        
        return $product ? $this->mapper->toDto($product) : null;
    }

    public function findByIdAny(ProductId $id): ?ProductDTO
    {
        $product = $this->productRepository->findByIdAny($id->getValue());
        
        return $product ? $this->mapper->toDto($product) : null;
    }

    public function findByIdForUser(ProductId $id, UserId $userId): ?ProductDTO
    {
        $product = $this->productRepository->findByIdForUserInt($id->getValue(), $userId->toInt());
        
        return $product ? $this->mapper->toDto($product) : null;
    }

    public function getUserProductCount(UserId $userId): int
    {
        return $this->productRepository->getUserProductCountInt($userId->toInt());
    }

    public function create(ProductWriteData $writeData): ProductDTO
    {
        $eloquentProduct = $this->productRepository->create($writeData->toArray());
        
        return $this->mapper->toDto($eloquentProduct);
    }

    public function update(ProductId $id, ProductWriteData $writeData): ProductDTO
    {
        $eloquentProduct = $this->productRepository->updateById($id->getValue(), $writeData->toArray());
        
        return $this->mapper->toDto($eloquentProduct);
    }

    public function delete(ProductId $id): void
    {
        $this->productRepository->deleteById($id->getValue());
    }

    public function deleteByUserId(int $userId): int
    {
        return $this->productRepository->deleteByUserId($userId);
    }
}

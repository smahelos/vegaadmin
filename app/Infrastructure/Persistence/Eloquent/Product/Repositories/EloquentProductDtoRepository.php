<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentProductMapper;
use App\Models\InvoiceProduct;
use Illuminate\Database\QueryException;

/**
 * Eloquent implementation of Product DTO repository.
 * Delegates to existing EloquentProductRepository and maps results to DTOs.
 */
class EloquentProductDtoRepository implements ProductDtoReadRepositoryInterface, ProductDtoWriteRepositoryInterface
{
    /** MySQL ER_ROW_IS_REFERENCED_2: a foreign key with ON DELETE RESTRICT refused the delete */
    private const ROW_IS_REFERENCED = 1451;

    public function __construct(
        private readonly EloquentProductRepository $productRepository,
        private readonly EloquentProductMapper $mapper,
    ) {}

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

    public function isUsedOnInvoices(ProductId $id): bool
    {
        return InvoiceProduct::where('product_id', $id->getValue())->exists();
    }

    /**
     * @throws ProductInUseException when an invoice line refers to the product
     */
    public function delete(ProductId $id): void
    {
        try {
            $this->productRepository->deleteById($id->getValue());
        } catch (QueryException $e) {
            // the service checks for invoice lines first; this catches one added in the meantime
            if (($e->errorInfo[1] ?? null) !== self::ROW_IS_REFERENCED) {
                throw $e;
            }

            throw ProductInUseException::onInvoices($id, $e);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\Validation\ProductCreationValidator;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class ProductService
{
    public function __construct(
        private readonly ProductDtoReadRepositoryInterface $productReadRepository,
        private readonly ProductDtoWriteRepositoryInterface $productWriteRepository,
        private readonly ProductCreationValidator $validator,
    ) {}

    public function createProduct(WriteScope $scope, ProductWriteData $writeData): ProductDTO
    {
        $validatedData = $this->validator->validateForCreation(
            $writeData->toArray(),
            $scope->owner->toInt(),
        );

        // The owner's first product is their default one
        if ($this->productReadRepository->getUserProductCount($scope->owner) === 0) {
            $validatedData['is_default'] = true;
        }

        return $this->productWriteRepository->create(
            $scope,
            ProductWriteData::fromArray($validatedData),
        );
    }

    public function updateProduct(
        ProductId $productId,
        WriteScope $scope,
        ProductWriteData $writeData,
    ): ProductDTO {
        $validatedData = $this->validator->validateForUpdate($writeData->toArray());

        return $this->productWriteRepository->update(
            $productId,
            $scope,
            ProductWriteData::fromArray($validatedData),
        );
    }

    /**
     * @throws ProductInUseException when an invoice line refers to the product
     */
    public function deleteProduct(ProductId $productId, WriteScope $scope): void
    {
        if ($this->productWriteRepository->isUsedOnInvoices($productId)) {
            throw ProductInUseException::onInvoices($productId);
        }

        $this->productWriteRepository->delete($productId, $scope);
    }
}

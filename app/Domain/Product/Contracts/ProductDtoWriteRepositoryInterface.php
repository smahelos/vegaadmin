<?php

declare(strict_types=1);

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

interface ProductDtoWriteRepositoryInterface
{
    /** The owner comes from the scope, never from the data. */
    public function create(WriteScope $scope, ProductWriteData $writeData): ProductDTO;

    /** The owner stays: a product never moves to another owner. */
    public function update(
        ProductId $id,
        WriteScope $scope,
        ProductWriteData $writeData,
    ): ProductDTO;

    public function isUsedOnInvoices(ProductId $id): bool;

    /**
     * @throws ProductInUseException when an invoice line refers to the product
     */
    public function delete(ProductId $id, WriteScope $scope): void;
}

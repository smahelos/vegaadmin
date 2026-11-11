<?php

declare(strict_types=1);

namespace App\Application\Product\Commands;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Services\ProductService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class UpdateProductHandler
{
    public function __construct(
        private readonly ProductDtoReadRepositoryInterface $productReads,
        private readonly ProductService $products,
    ) {}

    public function handle(UpdateProduct $command, WriteScope $scope): ProductDTO
    {
        if ($this->productReads->findById($command->id, $scope->read) === null) {
            throw new ProductNotFoundException($command->id);
        }

        return $this->products->updateProduct(
            $command->id,
            $scope,
            $command->data,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetProductHandler
{
    public function __construct(
        private readonly ProductDtoReadRepositoryInterface $products,
    ) {}

    public function handle(GetProduct $query, ReadScope $scope): ProductDTO
    {
        return $this->products->findById($query->id, $scope)
            ?? throw new ProductNotFoundException($query->id);
    }
}

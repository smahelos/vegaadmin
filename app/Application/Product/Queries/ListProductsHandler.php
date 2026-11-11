<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListProductsHandler
{
    public function __construct(
        private readonly ProductDtoReadRepositoryInterface $products,
    ) {}

    /**
     * @return PaginatedResult<ProductDTO>
     */
    public function handle(ListProducts $query, ReadScope $scope): PaginatedResult
    {
        return $this->products->search(
            $scope,
            $query->search,
            $query->page,
        );
    }
}

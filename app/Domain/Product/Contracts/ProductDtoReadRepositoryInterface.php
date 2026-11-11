<?php

declare(strict_types=1);

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;

interface ProductDtoReadRepositoryInterface
{
    /** Null when the product does not exist or belongs to another owner. */
    public function findById(ProductId $id, ReadScope $scope): ?ProductDTO;

    /**
     * @return PaginatedResult<ProductDTO>
     */
    public function search(
        ReadScope $scope,
        ProductSearch $search,
        PageRequest $page,
    ): PaginatedResult;

    public function getUserProductCount(UserId $userId): int;
}

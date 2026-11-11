<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<PaginatedResult<ProductDTO>> */
final readonly class ListProducts extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public ProductSearch $search,
        public PageRequest $page,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_VIEW);
    }
}

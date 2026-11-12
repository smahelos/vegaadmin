<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\DTO\CatalogProductDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListInvoiceCatalogProductsHandler
{
    public function __construct(
        private readonly CatalogProductReadRepositoryInterface $products,
    ) {}

    /** @return list<CatalogProductDTO> */
    public function handle(ListInvoiceCatalogProducts $query, ReadScope $scope): array
    {
        return $this->products->search(
            $scope,
            $query->currency,
            $query->search,
        );
    }
}

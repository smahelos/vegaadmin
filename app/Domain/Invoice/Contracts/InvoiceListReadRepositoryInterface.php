<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

interface InvoiceListReadRepositoryInterface
{
    /** @return PaginatedResult<InvoiceListDTO> */
    public function search(
        ReadScope $scope,
        InvoiceSearch $search,
        PageRequest $page,
    ): PaginatedResult;

    /** @return list<InvoicePartyFilterDTO> Includes archived sources for historical filtering. */
    public function businessFilters(ReadScope $scope): array;

    /** @return list<InvoicePartyFilterDTO> Includes archived sources for historical filtering. */
    public function contactFilters(ReadScope $scope): array;
}

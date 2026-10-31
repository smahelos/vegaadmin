<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceListReadRepositoryInterface
{
    /** @return PaginatedResult<InvoiceListDTO> */
    public function searchForUser(
        UserId $userId,
        InvoiceSearch $search,
        PageRequest $page,
    ): PaginatedResult;

    /** @return list<InvoicePartyFilterDTO> Includes archived sources for historical filtering. */
    public function businessFiltersForUser(UserId $userId): array;

    /** @return list<InvoicePartyFilterDTO> Includes archived sources for historical filtering. */
    public function contactFiltersForUser(UserId $userId): array;
}

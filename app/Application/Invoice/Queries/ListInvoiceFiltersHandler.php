<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Party\Enums\PartyKind;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListInvoiceFiltersHandler
{
    public function __construct(
        private readonly InvoiceListReadRepositoryInterface $invoices,
    ) {}

    /**
     * @return list<InvoicePartyFilterDTO>
     */
    public function handle(ListInvoiceFilters $query, ReadScope $scope): array
    {
        return match ($query->kind) {
            PartyKind::BUSINESS => $this->invoices->businessFilters($scope),
            PartyKind::CONTACT => $this->invoices->contactFilters($scope),
        };
    }
}

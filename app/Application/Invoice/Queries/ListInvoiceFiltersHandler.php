<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceListFiltersDTO;
use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListInvoiceFiltersHandler
{
    public function __construct(
        private readonly InvoiceListReadRepositoryInterface $invoices,
    ) {}

    public function handle(ListInvoiceFilters $query, ReadScope $scope): InvoiceListFiltersDTO
    {
        return new InvoiceListFiltersDTO(
            $this->invoices->businessFilters($scope),
            $this->invoices->contactFilters($scope),
        );
    }
}

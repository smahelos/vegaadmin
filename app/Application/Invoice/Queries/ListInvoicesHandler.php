<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;

final class ListInvoicesHandler
{
    public function __construct(
        private readonly InvoiceListReadRepositoryInterface $invoices,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * @return PaginatedResult<InvoiceListDTO>
     *
     * @throws BusinessNotFoundException
     * @throws ContactNotFoundException
     */
    public function handle(ListInvoices $query, ReadScope $scope): PaginatedResult
    {
        $search = $query->search;
        if ($search->businessId !== null && $this->businesses->findById($search->businessId, $scope) === null) {
            throw new BusinessNotFoundException($search->businessId);
        }
        if ($search->contactId !== null && $this->contacts->findById($search->contactId, $scope) === null) {
            throw new ContactNotFoundException($search->contactId);
        }

        return $this->invoices->search(
            $scope,
            $search,
            $query->page,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Invoice\Exceptions\InvoiceAccessDeniedException;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceListApplicationService
{
    public function __construct(
        private readonly InvoiceListReadRepositoryInterface $invoices,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly UserPermissionServiceInterface $permissions,
    ) {}

    /** @return PaginatedResult<InvoiceListDTO> */
    public function search(
        UserId $userId,
        InvoiceSearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $this->assertPermission($userId);
        if ($search->businessId !== null && $this->businesses->findByIdForUser($search->businessId, $userId) === null) {
            throw new BusinessNotFoundException($search->businessId);
        }
        if ($search->contactId !== null && $this->contacts->findByIdForUser($search->contactId, $userId) === null) {
            throw new ContactNotFoundException($search->contactId);
        }

        return $this->invoices->searchForUser(
            $userId,
            $search,
            $page,
        );
    }

    /** @return list<InvoicePartyFilterDTO> */
    public function businessFilters(UserId $userId): array
    {
        $this->assertPermission($userId);

        return $this->invoices->businessFiltersForUser($userId);
    }

    /** @return list<InvoicePartyFilterDTO> */
    public function contactFilters(UserId $userId): array
    {
        $this->assertPermission($userId);

        return $this->invoices->contactFiltersForUser($userId);
    }

    private function assertPermission(UserId $userId): void
    {
        if (! in_array(
            'frontend.can_view_invoice',
            $this->permissions->getUserPermissions($userId->toInt(), 'web'),
            true,
        )) {
            throw new InvoiceAccessDeniedException('The user cannot view invoices');
        }
    }
}

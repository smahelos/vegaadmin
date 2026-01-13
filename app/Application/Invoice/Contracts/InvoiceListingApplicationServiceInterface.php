<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contract for invoice listing and search operations
 */
interface InvoiceListingApplicationServiceInterface
{
    /**
     * Get all invoices for user
     */
    public function getAll(UserId $userId): Collection;

    /**
     * Get all invoices with filters applied
     */
    public function getAllWithFilters(UserId $userId, array $filters = []): Collection;

    /**
     * Get paginated invoices with search
     */
    public function getWithSearch(UserId $userId, array $searchParams = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get invoice by ID for viewing
     */
    public function getById(InvoiceId $invoiceId, UserId $userId): InvoiceDTO;

    /**
     * Get recent invoices for user
     */
    public function getRecent(UserId $userId, int $limit = 10): Collection;

    /**
     * Search invoices by various criteria
     */
    public function search(UserId $userId, string $query, array $fields = []): Collection;

    /**
     * Get invoices for specific date range
     */
    public function getByDateRange(UserId $userId, \DateTime $from, \DateTime $to): Collection;

    /**
     * Get invoices with specific criteria
     */
    public function getByCriteria(UserId $userId, array $criteria): Collection;
}

<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use Illuminate\Support\Collection;

/**
 * Contract for invoice status management services
 */
interface InvoiceStatusApplicationServiceInterface
{
    /**
     * Get invoices by status
     */
    public function getByStatus(UserId $userId, StatusId $statusId): Collection;

    /**
     * Change invoice status
     */
    public function changeStatus(InvoiceId $invoiceId, StatusId $statusId, UserId $userId): InvoiceDTO;

    /**
     * Get available statuses for invoice
     */
    public function getAvailableStatuses(InvoiceId $invoiceId, UserId $userId): Collection;

    /**
     * Get status history for invoice
     */
    public function getStatusHistory(InvoiceId $invoiceId, UserId $userId): Collection;

    /**
     * Check if status change is allowed
     */
    public function canChangeStatus(InvoiceId $invoiceId, StatusId $statusId, UserId $userId): bool;

    /**
     * Get invoices grouped by status
     */
    public function getGroupedByStatus(UserId $userId): array;

    /**
     * Get status statistics for user
     */
    public function getStatusStatistics(UserId $userId): array;
}

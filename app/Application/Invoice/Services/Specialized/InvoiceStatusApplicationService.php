<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceStatusApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice status management operations
 */
class InvoiceStatusApplicationService implements InvoiceStatusApplicationServiceInterface
{
    public function __construct(
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private StatusDtoRepositoryInterface $statusRepository
    ) {}

    /**
     * Get invoices by status
     */
    public function getByStatus(UserId $userId, StatusId $statusId): Collection
    {
        // This would require extending InvoiceReadRepositoryInterface with a method to filter by status
        // For now, we'll get all user invoices and filter by status
        $allInvoices = $this->invoiceReadRepository->allForUser($userId->toInt());
        
        return $allInvoices->filter(function ($invoice) use ($statusId) {
            return $invoice->payment_status_id === $statusId->getValue();
        });
    }

    /**
     * Change invoice status
     */
    public function changeStatus(InvoiceId $invoiceId, StatusId $statusId, UserId $userId): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }

        // Update the status through the service layer
        // This would typically go through a domain service or write repository
        $invoice->payment_status_id = $statusId->getValue();
        $invoice->save();
        
        return InvoiceDTO::fromArray($invoice->toArray());
    }

    /**
     * Get available statuses for invoice
     */
    public function getAvailableStatuses(InvoiceId $invoiceId, UserId $userId): Collection
    {
        // For now, return all available statuses
        // In more complex scenarios, this could be context-dependent
        $statuses = $this->statusRepository->getAllForDropdown();
        
        return collect($statuses);
    }

    /**
     * Get status history for invoice
     */
    public function getStatusHistory(InvoiceId $invoiceId, UserId $userId): Collection
    {
        // This would require a status history table/audit log
        // For now, return empty collection
        // TODO: Implement when status history tracking is available
        return collect();
    }

    /**
     * Check if status change is allowed
     */
    public function canChangeStatus(InvoiceId $invoiceId, StatusId $statusId, UserId $userId): bool
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            return false;
        }

        // Basic business rules for status transitions
        // These could be more sophisticated based on business requirements
        $currentStatusId = $invoice->payment_status_id;
        $newStatusId = $statusId->getValue();

        // Allow any status change for now
        // TODO: Implement specific business rules for status transitions
        return true;
    }

    /**
     * Get invoices grouped by status
     */
    public function getGroupedByStatus(UserId $userId): array
    {
        $allInvoices = $this->invoiceReadRepository->allForUser($userId->toInt());
        $statusMap = $this->statusRepository->getStatusIdSlugMap();
        
        $grouped = [];
        
        foreach ($allInvoices as $invoice) {
            $statusId = $invoice->payment_status_id ?? 'unknown';
            $statusSlug = $statusMap[$statusId] ?? 'unknown';
            
            if (!isset($grouped[$statusSlug])) {
                $grouped[$statusSlug] = [];
            }
            
            $grouped[$statusSlug][] = $invoice;
        }
        
        return $grouped;
    }

    /**
     * Get status statistics for user
     */
    public function getStatusStatistics(UserId $userId): array
    {
        $grouped = $this->getGroupedByStatus($userId);
        $statistics = [];
        
        foreach ($grouped as $status => $invoices) {
            $statistics[$status] = [
                'count' => count($invoices),
                'total_amount' => collect($invoices)->sum('payment_amount'),
            ];
        }
        
        return $statistics;
    }

    /**
     * Get all available statuses
     */
    public function getAllStatuses(): Collection
    {
        $statuses = $this->statusRepository->getAllForDropdown();
        return collect($statuses);
    }

    /**
     * Find status by slug
     */
    public function findStatusBySlug(string $slug): ?array
    {
        $statusDto = $this->statusRepository->findBySlug($slug);
        
        if (!$statusDto) {
            return null;
        }
        
        return [
            'id' => $statusDto->id,
            'slug' => $statusDto->slug,
            'name' => $statusDto->name,
        ];
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $paidStatusId = $this->statusRepository->findIdBySlug('paid');
        
        if (!$paidStatusId) {
            throw new \Exception("Paid status not found in system");
        }
        
        return $this->changeStatus($invoiceId, StatusId::fromInt($paidStatusId), $userId);
    }

    /**
     * Mark invoice as unpaid
     */
    public function markAsUnpaid(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $unpaidStatusId = $this->statusRepository->findIdBySlug('unpaid');
        
        if (!$unpaidStatusId) {
            throw new \Exception("Unpaid status not found in system");
        }
        
        return $this->changeStatus($invoiceId, StatusId::fromInt($unpaidStatusId), $userId);
    }
}

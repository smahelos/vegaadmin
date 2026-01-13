<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;

/**
 * Contract for invoice limit management services
 */
interface InvoiceLimitApplicationServiceInterface
{
    /**
     * Get total count of invoices for user
     */
    public function getInvoicesCount(UserId $userId): int;

    /**
     * Check if user can create new invoice
     */
    public function canCreateInvoice(UserId $userId): bool;

    /**
     * Get remaining invoice count for user
     */
    public function getRemainingInvoices(UserId $userId): int;

    /**
     * Get user's invoice limit
     */
    public function getUserLimit(UserId $userId): int;

    /**
     * Check if user has reached limit
     */
    public function hasReachedLimit(UserId $userId): bool;

    /**
     * Get limit information for user
     */
    public function getLimitInfo(UserId $userId): array;

    /**
     * Get usage statistics for user
     */
    public function getUsageStatistics(UserId $userId): array;

    /**
     * Check if user can upgrade to remove limits
     */
    public function canUpgrade(UserId $userId): bool;
}

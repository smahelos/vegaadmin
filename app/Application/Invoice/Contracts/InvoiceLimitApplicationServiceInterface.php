<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for invoice limit management services
 */
interface InvoiceLimitApplicationServiceInterface
{
    /**
     * Get limit information for user
     */
    public function getLimitInfo(UserId $userId): array;
}

<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for invoice listing and search operations
 */
interface InvoiceListingApplicationServiceInterface
{
    /**
     * Get invoice by ID for viewing
     */
    public function getById(InvoiceId $invoiceId, UserId $userId): InvoiceDTO;
}

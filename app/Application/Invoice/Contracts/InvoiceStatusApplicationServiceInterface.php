<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for invoice status management services
 */
interface InvoiceStatusApplicationServiceInterface
{
    /**
     * Change invoice status
     */
    public function changeStatus(
        InvoiceId $invoiceId,
        StatusId $statusId,
        UserId $userId,
    ): InvoiceDTO;
}

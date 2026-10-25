<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceStatusApplicationServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice status management operations
 */
class InvoiceStatusApplicationService implements InvoiceStatusApplicationServiceInterface
{
    public function __construct(
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
    ) {}

    /**
     * Change invoice status
     */
    public function changeStatus(
        InvoiceId $invoiceId,
        StatusId $statusId,
        UserId $userId,
    ): InvoiceDTO {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());

        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not found or access denied');
        }

        // Update the status through the service layer
        // This would typically go through a domain service or write repository
        $invoice->payment_status_id = $statusId->getValue();
        $invoice->save();

        return InvoiceDTO::fromArray($invoice->toArray());
    }
}

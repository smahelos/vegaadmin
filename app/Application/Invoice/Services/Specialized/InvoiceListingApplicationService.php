<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice listing and search operations
 */
class InvoiceListingApplicationService implements InvoiceListingApplicationServiceInterface
{
    public function __construct(
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
    ) {}

    /**
     * Get invoice by ID for viewing
     */
    public function getById(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());

        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not found or access denied');
        }

        return InvoiceDTO::fromArray($invoice->toArray());
    }
}

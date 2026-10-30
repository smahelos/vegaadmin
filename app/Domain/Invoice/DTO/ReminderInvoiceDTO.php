<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class ReminderInvoiceDTO
{
    public function __construct(
        public InvoiceId $id,
        public Money $total,
        public string $number,
        public InvoiceDueDate $dueDate,
        public DocumentPartyDTO $supplier,
        public DocumentPartyDTO $customer,
    ) {}
}

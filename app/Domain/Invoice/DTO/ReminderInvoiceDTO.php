<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Party\DTO\DocumentPartyDTO;

final readonly class ReminderInvoiceDTO
{
    public function __construct(
        public string $number,
        public InvoiceDueDate $dueDate,
        public DocumentPartyDTO $supplier,
        public DocumentPartyDTO $customer,
    ) {}
}

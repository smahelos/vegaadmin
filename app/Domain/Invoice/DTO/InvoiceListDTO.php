<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class InvoiceListDTO
{
    public function __construct(
        public InvoiceId $id,
        public ?ContactId $contactId,
        public string $number,
        public string $customerName,
        public Money $amount,
        public ?InvoiceDueDate $dueDate,
        public ?InvoiceStatus $status,
        public ?PaymentMethodCode $paymentMethod,
        public string $statusColor,
    ) {}
}

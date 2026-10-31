<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\PartyPrintDTO;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

/**
 * Domain DTO with all information needed to render an invoice, without presentation concerns.
 */
final readonly class PrintableInvoiceDTO
{
    /**
     * @param  array<int, PrintableInvoiceItemDTO>  $items
     */
    public function __construct(
        public InvoiceId $id,
        public ?string $number,
        public ?DateTimeImmutable $issueDate,
        public ?DateTimeImmutable $taxPointDate,
        public ?InvoiceDueDate $dueDate,
        public string $currency,
        public ?string $template,
        public array $items,
        public Money $subtotal,
        public Money $totalTax,
        public Money $totalAmount,
        public PartyPrintDTO $supplier,
        public PartyPrintDTO $customer,
        public ?DocumentAccountDTO $account,
        public ?string $supplierLogo,
        public ?string $variableSymbol,
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public ?PaymentMethodCode $paymentMethod,
        public ?string $notes,
    ) {}
}

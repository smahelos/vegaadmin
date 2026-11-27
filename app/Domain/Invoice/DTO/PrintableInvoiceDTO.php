<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\PartyPrintDTO;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\VariableSymbol;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

/**
 * Domain DTO with all information needed to render an invoice, without presentation concerns.
 */
final readonly class PrintableInvoiceDTO
{
    /**
     * @param  array<int, PrintableInvoiceItemDTO>  $items
     * @param  bool  $vatPayer  A non-payer's invoice states no VAT
     * @param  bool  $draft  A draft's preview is no tax document: it has no number, dates or QR payment
     * @param  string|null  $registerEntry  The supplier's entry in a public register
     */
    public function __construct(
        public InvoiceId $id,
        public ?string $number,
        public ?DateTimeImmutable $issueDate,
        public ?DateTimeImmutable $taxPointDate,
        public ?InvoiceDueDate $dueDate,
        public int $dueIn,
        public string $currency,
        public InvoiceTemplate $template,
        public array $items,
        public InvoiceTotals $totals,
        public bool $vatPayer,
        public Money $totalAmount,
        public PartyPrintDTO $supplier,
        public PartyPrintDTO $customer,
        public ?DocumentAccountDTO $account,
        public ?string $supplierLogo,
        public ?VariableSymbol $variableSymbol,
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public ?PaymentMethodCode $paymentMethod,
        public ?string $notes,
        public bool $draft,
        public ?string $registerEntry,
    ) {}
}

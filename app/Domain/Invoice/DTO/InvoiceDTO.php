<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

final readonly class InvoiceDTO
{
    /**
     * @param  Money  $subtotal  Sum of the line bases
     * @param  Money  $totalTax  Sum of the line VAT amounts
     * @param  Money  $totalAmount  The stored amount to pay
     * @param  list<InvoiceProductDTO>  $items
     */
    public function __construct(
        public InvoiceId $id,
        public DocumentParties $parties,
        public string $number,
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public DateTimeImmutable $issueDate,
        public DateTimeImmutable $taxPointDate,
        public int $dueIn,
        public Money $subtotal,
        public Money $totalTax,
        public Money $totalAmount,
        public ?PaymentMethodId $paymentMethodId,
        public ?string $text,
        public InvoiceTemplate $template,
        public InvoiceStatus $status,
        public array $items,
    ) {}
}

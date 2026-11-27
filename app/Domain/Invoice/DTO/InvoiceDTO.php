<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Translation\Enums\Language;

final readonly class InvoiceDTO
{
    /**
     * @param  InvoiceTotals  $totals  Computed from the lines of a draft, stored at issue for an issued invoice
     * @param  Money  $totalAmount  The stored amount to pay
     * @param  list<InvoiceProductDTO>  $items
     * @param  InvoiceIssue|null  $issue  Null for a draft
     */
    public function __construct(
        public InvoiceId $id,
        public DocumentParties $parties,
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public int $dueIn,
        public InvoiceTotals $totals,
        public Money $totalAmount,
        public ?PaymentMethodId $paymentMethodId,
        public ?string $text,
        public InvoiceTemplate $template,
        public Language $language,
        public InvoiceStatus $status,
        public array $items,
        public ?InvoiceIssue $issue,
    ) {}
}

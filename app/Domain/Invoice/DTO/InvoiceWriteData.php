<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use DateTimeImmutable;

/**
 * What the user enters for an invoice; an update replaces all of it. The owner, the parties and the
 * total derived from the lines are passed beside it.
 */
final readonly class InvoiceWriteData
{
    public function __construct(
        public InvoiceNumber $number,
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public DateTimeImmutable $issueDate,
        public DateTimeImmutable $taxPointDate,
        public int $dueIn,
        public Currency $currency,
        public InvoiceStatus $status,
        public PaymentMethodId $paymentMethodId,
        public ?string $text,
        public InvoiceTemplate $template,
    ) {}
}

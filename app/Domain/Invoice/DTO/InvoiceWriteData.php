<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Translation\Enums\Language;

/**
 * What the user enters on a draft; an update replaces all of it. The owner, the parties and the total derived
 * from the lines are passed beside it; the number and the dates are given at issue.
 */
final readonly class InvoiceWriteData
{
    public function __construct(
        public ?string $constantSymbol,
        public ?string $specificSymbol,
        public int $dueIn,
        public Currency $currency,
        public PaymentMethodId $paymentMethodId,
        public ?string $text,
        public InvoiceTemplate $template,
        public Language $language,
    ) {}
}

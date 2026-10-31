<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class InvoiceLineAmountDTO
{
    public function __construct(public Money $total, public Money $tax) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class UserStatisticsDTO
{
    public function __construct(
        public int $invoiceCount,
        public int $contactCount,
        public int $businessCount,
        public Money $totalAmount,
    ) {}
}

<?php

namespace App\Domain\Analytics\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Represents a single monthly aggregate row (month string + total amount).
 */
class MonthlyStatDTO
{
    public function __construct(
        public readonly string $month, // Format: YYYY-MM
        public readonly Money $total,
    ) {}

    /**
     * @return array{month:string,total:float}
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'total' => $this->total->toFloat(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class CustomerTotalDTO
{
    public function __construct(
        public ?ContactId $contactId,
        public string $customerName,
        public Money $totalAmount,
    ) {}
}

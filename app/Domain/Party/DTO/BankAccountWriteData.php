<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Payment\ValueObjects\BankAccount;

final readonly class BankAccountWriteData
{
    public function __construct(
        public ?string $label,
        public BankAccount $account,
        public ?string $bankName,
    ) {}
}

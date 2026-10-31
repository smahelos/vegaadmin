<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class QrPaymentDTO
{
    public function __construct(
        public ?string $variableSymbol,
        public Money $amount,
        public ?BankAccount $account,
        public ?string $message = null,
    ) {}

    /** QR formats carry two decimal places, rounded half up without a binary float conversion. */
    public function amountDecimal(): string
    {
        $rounded = $this->amount->multiplyBy(
            '1',
            2,
            Money::ROUND_HALF_UP,
        )->getAmount();
        $parts = explode(
            '.',
            $rounded,
            2,
        );

        return $parts[0] . '.' . str_pad(
            $parts[1] ?? '',
            2,
            '0',
        );
    }
}

<?php

namespace App\Domain\Payment\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Domain DTO with minimal information required to generate a QR payment code.
 */
class QrPaymentPayload
{
    public function __construct(
        public readonly ?string $variableSymbol,
        public readonly Money $amount,
        public readonly ?string $accountNumber,
        public readonly ?string $bankCode,
        public readonly ?string $iban,
        public readonly ?string $countryCode = null,
        public readonly ?string $message = null,
    ) {}
}

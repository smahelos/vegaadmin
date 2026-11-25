<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Payment\ValueObjects\VariableSymbol;
use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class QrPaymentDTO
{
    public function __construct(
        public ?VariableSymbol $variableSymbol,
        public Money $amount,
        public ?BankAccount $account,
        public ?string $message = null,
    ) {}

    /**
     * The message in ASCII, cut to the format's length: QR Platba reads ISO-8859-1, which lacks most Czech letters,
     * and banks show the message as plain text.
     */
    public function asciiMessage(int $length): string
    {
        $ascii = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [:^ASCII:] Remove',
            mb_scrub($this->message ?? '', 'UTF-8'),
        );

        // Transliteration fails only on invalid UTF-8, which mb_scrub has replaced
        return substr(
            (string) $ascii,
            0,
            $length,
        );
    }

    /** QR formats carry two decimal places, rounded half up without a binary float conversion. */
    public function amountDecimal(): string
    {
        $money = $this->amount->multiplyBy(
            '1',
            2,
            Money::ROUND_HALF_UP,
        );
        $rounded = $money->amount;
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

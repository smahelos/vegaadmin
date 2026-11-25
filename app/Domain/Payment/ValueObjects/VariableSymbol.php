<?php

declare(strict_types=1);

namespace App\Domain\Payment\ValueObjects;

use App\Domain\Payment\Exceptions\InvalidVariableSymbolException;

/**
 * Variable symbol of a Czech or Slovak bank payment: one to ten digits.
 */
final readonly class VariableSymbol
{
    /** @throws InvalidVariableSymbolException */
    public function __construct(
        public string $value,
    ) {
        if (preg_match('/^\d{1,10}\z/', $value) !== 1) {
            throw new InvalidVariableSymbolException($value);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Shared\Number\Contracts;

/**
 * Formats decimal numbers that are not amounts (quantities, rates) for people in the conventions of a language.
 */
interface NumberFormatterInterface
{
    /** @param  string  $decimal  Decimal string, e.g. "1.500" */
    public function format(string $decimal, string $locale): string;
}

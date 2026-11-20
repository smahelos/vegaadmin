<?php

declare(strict_types=1);

namespace App\Domain\Shared\Money\Contracts;

use App\Domain\Shared\Money\Enums\Currency;

interface CurrencyNamesInterface
{
    public function name(Currency $currency, string $locale): string;
}

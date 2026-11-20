<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Shared;

use App\Domain\Shared\Money\Contracts\CurrencyNamesInterface;
use App\Domain\Shared\Money\Enums\Currency;

final class LocaleTaggedCurrencyNames implements CurrencyNamesInterface
{
    public function name(Currency $currency, string $locale): string
    {
        return $locale . ':' . strtolower($currency->value);
    }
}

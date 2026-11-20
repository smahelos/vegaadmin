<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Money;

use App\Domain\Shared\Money\Contracts\CurrencyNamesInterface;
use App\Domain\Shared\Money\Enums\Currency;
use ResourceBundle;

/**
 * Currency names from ICU, so every currency is named in every locale without translation files.
 */
final class IntlCurrencyNames implements CurrencyNamesInterface
{
    public function name(Currency $currency, string $locale): string
    {
        $bundle = ResourceBundle::create($locale, 'ICUDATA-curr');

        // ICU keeps [symbol, name] per code
        return $bundle['Currencies'][$currency->value][1];
    }
}

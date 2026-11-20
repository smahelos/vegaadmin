<?php

declare(strict_types=1);

namespace App\Application\Shared\Money\Presenters;

use App\Domain\Shared\Money\Contracts\CurrencyNamesInterface;
use App\Domain\Shared\Money\Enums\Currency;

/**
 * Currencies for the currency selects of invoices, invoice items, expenses and products.
 */
final class CurrencyOptionsPresenter
{
    public function __construct(
        private readonly CurrencyNamesInterface $names,
    ) {}

    /**
     * @return array<string, string> "CZK – česká koruna" by currency code, in the order of the enum
     */
    public function options(string $locale): array
    {
        $options = [];
        foreach (Currency::cases() as $currency) {
            $options[$currency->value] = $currency->value . ' – ' . $this->names->name($currency, $locale);
        }

        return $options;
    }
}

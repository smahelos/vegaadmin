<?php

namespace App\Domain\Shared\Money\Contracts;

interface CurrencyServiceInterface
{
    /**
     * Get list of all available currencies (code => code)
     * @return array<string,string>
     */
    public function getAllCurrencies(): array;

    /**
     * Get list of commonly used currencies (subset)
     * @return array<string,string>
     */
    public function getCommonCurrencies(): array;
}

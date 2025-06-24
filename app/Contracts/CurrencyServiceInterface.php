<?php

namespace App\Contracts;

interface CurrencyServiceInterface
{
    /**
     * Get list of all available currencies
     * 
     * @return array Array with currency codes as keys and currency codes as values
     */
    public function getAllCurrencies(): array;

    /**
     * Get list of common currencies
     * 
     * @return array Array with currency codes as keys and currency codes as values
     */
    public function getCommonCurrencies(): array;
}

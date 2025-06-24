<?php

namespace App\Contracts;

interface BankServiceInterface
{
    /**
     * Get banks for dropdown
     * 
     * @param string $country
     * @return array
     */
    public function getBanksForDropdown(string $country = 'CZ'): array;

    /**
     * Get banks for JS
     * 
     * @param string $country
     * @return array
     */
    public function getBanksForJs(string $country = 'CZ'): array;
}

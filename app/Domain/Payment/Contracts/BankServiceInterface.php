<?php

namespace App\Domain\Payment\Contracts;

interface BankServiceInterface
{
    /**
     * Get banks for dropdown
     */
    public function getBanksForDropdown(string $country = 'CZ'): array;

    /**
     * Get banks for JS
     */
    public function getBanksForJs(string $country = 'CZ'): array;
}

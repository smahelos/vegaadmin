<?php

namespace App\Application\Payment\Contracts;

interface BankApplicationServiceInterface
{
    /**
     * Get banks for select dropdowns.
     * @return array<int, array{code:string, name:string}>
     */
    public function getBanksForDropdown(): array;

    /**
     * Get banks data formatted for JS widgets.
     * @return array<string, mixed>
     */
    public function getBanksForJs(): array;
}

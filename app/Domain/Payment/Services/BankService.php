<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Domain\Payment\Contracts\BankDtoRepository;

class BankService implements BankServiceInterface
{
    public function __construct(
        private readonly BankDtoRepository $bankDtoRepository
    ) {
    }
    
    /**
     * Get list of banks with codes for dropdown
     * 
     * @param string $country Country code (default: CZ)
     * @return array
     */
    public function getBanksForDropdown(string $country = 'CZ'): array
    {
        $banks = $this->bankDtoRepository->getAllBanks($country);

        return array_map(fn($bank) => [
            'text' => $bank->name . ' (' . $bank->code . ')',
            'value' => $bank->code,
            'swift' => $bank->swift,
        ], $banks);
    }

    /**
     * Get banks data in format suitable for JavaScript
     * 
     * @param string $country Country code (default: CZ)
     * @return array
     */
    public function getBanksForJs(string $country = 'CZ'): array
    {
        $banks = $this->bankDtoRepository->getAllBanks($country);

        $banksData = [];
        foreach ($banks as $bank) {
            $banksData[$bank->code]['text'] = $bank->name . ' (' . $bank->code . ')';
            $banksData[$bank->code]['swift'] = $bank->swift;
        }

        return $banksData;
    }
}

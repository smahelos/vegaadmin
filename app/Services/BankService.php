<?php

namespace App\Services;

use App\Contracts\BankServiceInterface;
use App\Models\Bank;

class BankService implements BankServiceInterface
{
    /**
     * Get list of banks with codes for dropdown
     * 
     * @param string $country Country code (default: CZ)
     * @return array
     */
    public function getBanksForDropdown(string $country = 'CZ'): array
    {
        $banks = Bank::where('country', $country)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        foreach ($banks as $key => $bank) {
            $banks[$key]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banks[$key]['value'] = $bank['code'];
            $banks[$key]['swift'] = $bank['swift']; 
        }
        
        $banks[0] = __('suppliers.fields.select_bank');

        return $banks;
    }

    /**
     * Get banks data in format suitable for JavaScript
     * 
     * @param string $country Country code (default: CZ)
     * @return array
     */
    public function getBanksForJs(string $country = 'CZ'): array
    {
        $banks = Bank::where('country', $country)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $banksData = [];
        foreach ($banks as $bank) {
            $banksData[$bank['code']]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banksData[$bank['code']]['swift'] = $bank['swift']; 
        }

        return $banksData;
    }
}

<?php

namespace App\Services;

use App\Models\Tax;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaxesService
{
    /**
     * Získá seznam všech kategorií
     * 
     * @return array
     */
    public function getAllTaxes(): array
    {
        $cacheKey = "taxes";

        return Cache::remember($cacheKey, 60 * 5, function () {
            $query = Tax::query();
            
            $taxes = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($taxes as $tax) {
                $result[$tax->slug] = $tax->name;
            }
            
            return $result;
        });
    }
    /**
     * Získá seznam všech kategorií
     * 
     * @return array
     */
    public function getAllTaxesForSelect(): array
    {
        $cacheKey = "taxes_for_select";

        return Cache::remember($cacheKey, 60 * 5, function () {
            $query = Tax::query();
            
            $taxes = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($taxes as $tax) {
                $result[$tax->id] = $tax->name;
            }
            
            return $result;
        });
    }

    /**
     * Vymaže cache s příkazy
     */
    public function clearCategoriesCache(): void
    {   
        // Vymaže cache pro všechny daně
        Cache::forget("taxes");
    }
}

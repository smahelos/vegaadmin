<?php

namespace App\Services;

use App\Models\Tax;
use Illuminate\Support\Facades\Cache;

class TaxesService
{
    /**
     * Get all taxes as slug => name array
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
     * Get all taxes as id => name array for select fields
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
     * Clear all tax-related cache
     */
    public function clearCategoriesCache(): void
    {   
        // Clear cache for all taxes
        Cache::forget("taxes");
        Cache::forget("taxes_for_select");
    }
}

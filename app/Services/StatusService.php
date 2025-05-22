<?php

namespace App\Services;

use App\Models\StatusCategory;
use Illuminate\Support\Facades\Cache;

class StatusService
{
    /**
     * Get all status categories
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        $cacheKey = "status_categories";

        return Cache::remember($cacheKey, 60 * 5, function () {
            $query = StatusCategory::query();
            
            $categories = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($categories as $category) {
                $result[$category->id] = $category->name;
            }
            
            return $result;
        });
    }

    /**
     * Delete all categories cache
     */
    public function clearCategoriesCache(): void
    {   
        // Delete cache for all categories
        $categories = StatusCategory::pluck('slug')->toArray();
        foreach ($categories as $slug) {
            Cache::forget("statuses_by_category:{$slug}:0");
            Cache::forget("statuses_by_category:{$slug}:1");
        }

        Cache::forget("status_categories");
    }
}

<?php

namespace App\Services;

use App\Contracts\ProductsServiceInterface;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductsService implements ProductsServiceInterface
{
    /**
     * Get all product categories
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        $cacheKey = "product_categories";

        return Cache::remember($cacheKey, 60 * 5, function () {
            $query = ProductCategory::query();
            
            $categories = $query->orderBy('name')->get();
            
            $result = [];
            foreach ($categories as $category) {
                $result[$category->id] = $category->name;
            }
            
            return $result;
        });
    }

    /**
     * Get all suppliers for the current user
     * 
     * @return array
     */
    public function getAllSuppliers(): array
    {
        $cacheKey = "product_suppliers";

        return Cache::remember($cacheKey, 60 * 5, function () {
            $query = Supplier::query();
            $query->where('user_id', Auth::id());

            $suppliers = $query->orderBy('name')->get();

            $result = [];
            foreach ($suppliers as $supplier) {
                $result[$supplier->id] = $supplier->name;
            }
            
            return $result;
        });
    }

    /**
     * Delete cache for all categories
     */
    public function clearCategoriesCache(): void
    {   
        // Delete cache for all categories
        $categories = ProductCategory::pluck('slug')->toArray();
        foreach ($categories as $slug) {
            Cache::forget("products_by_category:{$slug}:0");
            Cache::forget("products_by_category:{$slug}:1");
        }
        
        Cache::forget("product_categories");
    }

    /**
     * Handles product image processing
     * 
     * @param UploadedFile|null $image
     * @param string|null $oldImage
     * @return string|null
     */
    public function handleProductImage(?UploadedFile $image, ?string $oldImage = null): ?string
    {
        $disk = "public";
        $destination_path = "products";

        // If no new image and not removing old one
        if ($image === null && $oldImage !== null && !request()->has('image_remove')) {
            return $oldImage;
        }
        
        // Delete old image if exists
        if ($oldImage !== null) {
            Storage::disk($disk)->delete($oldImage);
        }

        // If removing image (and no new one uploaded)
        if ($image === null) {
            return null;
        }
        
        // Handle new image upload
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $filename = md5($image->getClientOriginalName().time()).'.'.$image->getClientOriginalExtension();
            $image->storeAs($destination_path, $filename, $disk);
            return $destination_path.'/'.$filename;
        }
        
        return null;
    }
}

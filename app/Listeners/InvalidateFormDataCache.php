<?php

namespace App\Listeners;

use App\Events\FormDataChanged;
use App\Contracts\CacheServiceInterface;
use App\Contracts\ProductServiceInterface;

class InvalidateFormDataCache
{
    /**
     * Cache service instance
     *
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * Product service instance
     *
     * @var ProductServiceInterface
     */
    protected $productService;

    /**
     * Create the event listener
     *
     * @param CacheServiceInterface $cacheService
     * @param ProductServiceInterface $productService
     */
    public function __construct(
        CacheServiceInterface $cacheService,
        ProductServiceInterface $productService
    ) {
        $this->cacheService = $cacheService;
        $this->productService = $productService;
    }

    /**
     * Handle the event
     *
     * @param FormDataChanged $event
     * @return void
     */
    public function handle(FormDataChanged $event): void
    {
        // Invalidate specific form data cache based on data type
        switch ($event->dataType) {
            case 'products':
                $this->cacheService->invalidateTags(['form_data', 'products']);
                break;
            case 'categories':
                $this->cacheService->invalidateTags(['form_data', 'categories']);
                // Also invalidate product form data since categories are used in product forms
                $this->productService->invalidateFormDataCache();
                break;
            case 'taxes':
                $this->cacheService->invalidateTags(['form_data', 'taxes']);
                // Also invalidate product form data since taxes are used in product forms
                $this->productService->invalidateFormDataCache();
                break;
            default:
                // Invalidate all form data cache
                $this->cacheService->invalidateTags(['form_data']);
                break;
        }
    }
}

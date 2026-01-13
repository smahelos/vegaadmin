<?php

namespace App\Domain\Shared\Listeners;

use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;

class InvalidateFormDataCache
{
    /**
     * Cache service instance
     *
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * Create the event listener
     *
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(
        CacheServiceInterface $cacheService,
    ) {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event
     *
     * @param FormDataChanged $event
     * @return void
     */
    public function handle(FormDataChanged $event): void
    {
        // Invalidate specific form data cache based on change type
        switch ($event->changeType) {
            case 'products':
                $this->cacheService->invalidateTags(['form_data', 'products']);
                break;
            case 'categories':
                $this->cacheService->invalidateTags(['form_data', 'categories']);
                // Also invalidate product form data since categories are used in product forms
                $this->cacheService->invalidateTags(['form_data', 'products']);
                break;
            case 'taxes':
                $this->cacheService->invalidateTags(['form_data', 'taxes']);
                // Also invalidate product form data since taxes are used in product forms
                $this->cacheService->invalidateTags(['form_data', 'products']);
                break;
            default:
                // Invalidate all form data cache
                $this->cacheService->invalidateTags(['form_data']);
                break;
        }
    }
}

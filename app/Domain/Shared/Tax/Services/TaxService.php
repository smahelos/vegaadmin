<?php

namespace App\Domain\Shared\Tax\Services;

use App\Domain\Shared\Tax\Contracts\TaxServiceInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;

/**
 * Invoice domain Taxes service.
 * Provides cached lookups for tax entities for form selections.
 */
class TaxService implements TaxServiceInterface
{
    /** Cache key for slug keyed taxes */
    private const CACHE_TAXES = 'taxes';
    /** Cache key for id keyed taxes */
    private const CACHE_TAXES_SELECT = 'taxes_for_select';
    /** Cache TTL seconds (5 minutes) */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly TaxDtoReadRepositoryInterface $taxesReadRepository
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getAllTaxes(): array
    {
        return $this->cacheService->remember(
            self::CACHE_TAXES,
            function () {
                return $this->taxesReadRepository->getAllTaxes();
            },
            self::CACHE_TTL,
            ['form_data', 'taxes']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllTaxesForSelect(): array
    {
        return $this->cacheService->remember(
            self::CACHE_TAXES_SELECT,
            function () {
                return $this->taxesReadRepository->getAllTaxesForSelect();
            },
            self::CACHE_TTL,
            ['form_data', 'taxes']
        );
    }

    /**
     * Clear all tax related cache entries.
     */
    public function clearCategoriesCache(): void
    {
        // Use tag invalidation to clear all related entries
        $this->cacheService->invalidateTags(['form_data', 'taxes']);
    }
}

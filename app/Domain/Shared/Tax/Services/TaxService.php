<?php

namespace App\Domain\Shared\Tax\Services;

use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxServiceInterface;

/**
 * Invoice domain Taxes service.
 * Provides cached lookups for tax entities for form selections.
 */
class TaxService implements TaxServiceInterface
{
    /** Cache key for id keyed taxes */
    private const CACHE_TAXES_SELECT = 'taxes_for_select';

    /** Cache TTL seconds (5 minutes) */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly TaxDtoReadRepositoryInterface $taxesReadRepository,
    ) {}

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
            ['form_data', 'taxes'],
        );
    }
}

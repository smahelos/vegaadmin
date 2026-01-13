<?php

namespace App\Domain\Shared\Status\Services;

use App\Domain\Shared\Status\Contracts\StatusServiceInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\Shared\Status\Contracts\StatusCategoryDtoRepositoryInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Status\ValueObjects\StatusCode;
use App\Domain\Shared\Translation\Contracts\TranslatorInterface;

/**
 * Domain StatusService implementation.
 * Caches category list and bidirectional status id/slug maps for cross-domain reuse.
 */
class StatusService implements StatusServiceInterface
{
    /** Base cache prefix */
    private const CACHE_PREFIX = 'status';

    /** TTL (seconds) for category and map caches */
    private const CACHE_TTL = 300; // 5 minutes

    /** Build namespaced cache key */
    private static function key(string $suffix): string
    {
        return self::CACHE_PREFIX . ':' . $suffix;
    }

    /** @var bool Lazy enum sync guard */
    private bool $enumSyncChecked = false;

    public function __construct(
        private CacheServiceInterface $cacheService,
        private StatusDtoRepositoryInterface $statusDtoRepository,
        private StatusCategoryDtoRepositoryInterface $statusCategoryDtoRepository,
        private TranslatorInterface $translator
    )
    {}

    /** @inheritDoc */
    public function getAllCategories(): array
    {
        return $this->cacheService->remember(self::key('categories'), function (): array {
            return $this->statusCategoryDtoRepository->getAllForDropdown();
        }, self::CACHE_TTL);
    }

    /** @inheritDoc */
    public function getIdToSlugMap(): array
    {
        $map = $this->cacheService->remember(self::key('id_to_slug'), fn (): array => $this->statusDtoRepository->getStatusSlugIdMap(), self::CACHE_TTL);
        $this->ensureEnumSyncOnce(array_values($map));
        return $map;
    }

    /** @inheritDoc */
    public function getSlugToIdMap(): array
    {
        $map = $this->cacheService->remember(self::key('slug_to_id'), fn (): array => $this->statusDtoRepository->getStatusIdSlugMap(), self::CACHE_TTL);
        $this->ensureEnumSyncOnce(array_keys($map));
        return $map;
    }

    /** Ensure all enum slugs exist in DB (run once per request). Extra DB slugs are allowed (admin-defined). */
    private function ensureEnumSyncOnce(array $dbSlugs): void
    {
        if ($this->enumSyncChecked) { return; }
        $enumSlugs = StatusCode::allSlugs();
        $missing = array_diff($enumSlugs, $dbSlugs);
        if ($missing) {
            throw new \RuntimeException('Database missing required system status slugs for enum cases: ' . implode(', ', $missing));
        }
        $this->enumSyncChecked = true;
    }

    /** @inheritDoc */
    public function clearStatusCaches(): void
    {
        $this->cacheService->forget(self::key('categories'));
        $this->cacheService->forget(self::key('id_to_slug'));
        $this->cacheService->forget(self::key('slug_to_id'));
        $this->cacheService->forget('status_categories'); // legacy compatibility
        // Future: dispatch(new StatusCachesCleared()) event if observers/async listeners needed.
    }

    /**
     * Resolve translated label for a given status slug. Falls back to enum label() then slug.
     */
    public function translateSlug(string $slug, ?string $locale = null): string
    {
        $key = 'statuses.' . $slug;
        if ($this->translator->has($key, $locale)) {
            return $this->translator->trans($key, [], $locale);
        }
        $enum = StatusCode::tryFromSlug($slug);
        return $enum ? $enum->label() : $slug;
    }
}

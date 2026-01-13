<?php

namespace App\Domain\Shared\Status\Contracts;

/**
 * StatusServiceInterface (Shared Domain)
 * Provides cached access to status categories and bidirectional status slug/id maps.
 */
interface StatusServiceInterface
{
    /**
     * Get associative map of category id => category name (cached).
     *
     * @return array<int,string>
     */
    public function getAllCategories(): array;

    /**
     * Get associative map of status id => status slug (cached).
     *
     * @return array<int,string>
     */
    public function getIdToSlugMap(): array;

    /**
     * Get associative map of status slug => status id (cached).
     *
     * @return array<string,int>
     */
    public function getSlugToIdMap(): array;

    /**
     * Invalidate all status related caches (categories + id/slug maps + legacy keys).
     *
     * @return void
     */
    public function clearStatusCaches(): void;

    /**
     * Translate a status slug to localized label (fallback to enum label or slug).
     */
    public function translateSlug(string $slug, ?string $locale = null): string;
}

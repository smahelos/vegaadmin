<?php

namespace App\Domain\Content\Contracts;

use App\Domain\Content\DTO\PageDTO;

/**
 * Contract for content page related read operations.
 * Defines retrieval, navigation, search and relationship helper methods.
 */
interface PageServiceInterface
{
    /**
     * Get the homepage for given or current locale.
     * Falls back to first published page if no homepage slug matches.
     *
     * @param string|null $locale Locale to search slugs in (null = current app locale)
    * @return PageDTO|null Homepage DTO or null when none exists
     */
    public function getHomepage(?string $locale = null): ?PageDTO;

    /**
     * Resolve a page by localized slug; if not found and slug is numeric, tries by ID; finally searches other locales.
     *
     * @param string $slug Localized slug or numeric ID
     * @param string|null $locale Preferred locale for slug matching (null = current)
    * @return PageDTO|null Matching page or null when not found
     */
    public function getPageBySlug(string $slug, ?string $locale = null): ?PageDTO;
}

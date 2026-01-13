<?php

namespace App\Application\Content\Contracts;

use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read repository for Page and PageCategory queries (MODEL-BASED).
 *
 * Layering guideline:
 * - Application/Backpack layers should use this interface and work with Eloquent models (CRUD, filters, pagination).
 * - Domain layer MUST NOT depend on models; it should use PageDtoReadRepositoryInterface instead.
 * - Because of Domain layer interfaces using DTO/VOs, this interface is in Application layer.
 *   This interface can use Eloquent Models and Collections and is used by Application services
 *   for rendering UI (data for forms, blade templates, etc.) and NON-business logic with DB operations.
 *
 * @see \App\Domain\Content\Contracts\PageDtoReadRepositoryInterface
 *
 */
interface PageReadRepositoryInterface
{
    /** Find homepage by preferred slugs in given locale. */
    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?Page;

    /** Return first published page as fallback homepage. */
    public function firstPublished(): ?Page;

    /** Find published page by localized slug, eager loading common relations. */
    public function findPublishedBySlug(string $slug, string $locale): ?Page;

    /** Find published page by id, eager loading common relations. */
    public function findPublishedById(int $id): ?Page;

    /** Find published page by slug in any of provided locales (skipping one optionally). */
    public function findPublishedBySlugInLocales(string $slug, array $locales, ?string $skipLocale = null): ?Page;

    /** Get root navigation pages with published children ordered by sort order. */
    public function getRootNavigationPages(): Collection;

    /** Get published pages for a specific category ordered by sort order. */
    public function getPublishedByCategory(int $categoryId): Collection;

    /** Find page by id (published or not). */
    public function findAnyById(int $id): ?Page;

    /** Get categories that have published pages, eager loading only published ordered pages. */
    public function getCategoriesWithPublishedPages(): Collection;

    /** Search published pages by keyword in given locale across name, description and content. */
    public function searchPublished(string $keyword, string $locale): Collection;

    /** Get related published pages by category excluding one page, limited by parameter. */
    public function getRelatedByCategory(int $categoryId, int $excludePageId, int $limit): Collection;

    /** Get all published pages with their category, ordered by sort order and name. */
    public function getAllPublishedWithCategory(): Collection;

    /** Find category by localized slug in given locale. */
    public function findCategoryBySlug(string $slug, string $locale): ?PageCategory;
}

<?php

namespace App\Application\Content\Contracts;

use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;

interface PageApplicationServiceInterface
{
    /**
     * Get the homepage, optionally filtered by locale.
    * @param string|null $locale
    * @return Page|null
     */
    public function getHomepage(?string $locale = null): ?Page;

    /**
     * Get a page by its slug, optionally filtered by locale.
    * @param string $slug
    * @param string|null $locale
    * @return Page|null
     */
    public function getPageBySlug(string $slug, ?string $locale = null): ?Page;
    
    /**
     * Get root navigation pages with their published children.
     * @return Collection
     */
    public function getPagesForNavigation(): Collection;

    /**
     * Get all published pages in a specific category.
    * @param int $categoryId
    * @return Collection
     */
    public function getPagesByCategory(int $categoryId): Collection;

    /**
     * Get raw page content by its ID.
    * @param int $pageId
    * @return Page|null
     */
    public function getPageContentById(int $pageId): ?Page;

    /**
     * Get categories with their published pages.
     * @return Collection
     */
    public function getCategoriesWithPages(): Collection;

    /**
     * Search pages by keyword.
    * @param string $keyword
    * @param string|null $locale
    * @return Collection
     */
    public function searchPages(string $keyword, ?string $locale = null): Collection;

    /**
     * Get breadcrumbs for a specific page.
    * @param Page $page
    * @return array<int, array{name:string,slug:string}>
     */
    public function getBreadcrumbs(Page $page): array;

    /**
     * Get related pages for a specific page.
    * @param Page $page
    * @param int $limit
    * @return Collection
     */
    public function getRelatedPages(Page $page, int $limit = 5): Collection;

    /**
     * Get all published pages.
    * @return Collection
     */
    public function getAllPublishedPages(): Collection;

    /**
     * Get a category by its slug, optionally filtered by locale.
     * @param string $slug
     * @param string|null $locale
     * @return PageCategory|null
     */
    public function getCategoryBySlug(string $slug, ?string $locale = null): ?PageCategory;
}

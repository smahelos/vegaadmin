<?php

namespace App\Application\Content\Contracts;

use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;

interface PageApplicationServiceInterface
{
    /**
     * Get the homepage, optionally filtered by locale.
     */
    public function getHomepage(?string $locale = null): ?Page;

    /**
     * Get a page by its slug, optionally filtered by locale.
     */
    public function getPageBySlug(string $slug, ?string $locale = null): ?Page;

    /**
     * Get root navigation pages with their published children.
     */
    public function getPagesForNavigation(): Collection;

    /**
     * Get all published pages in a specific category.
     */
    public function getPagesByCategory(int $categoryId): Collection;

    /**
     * Get raw page content by its ID.
     */
    public function getPageContentById(int $pageId): ?Page;

    /**
     * Get categories with their published pages.
     */
    public function getCategoriesWithPages(): Collection;

    /**
     * Search pages by keyword.
     */
    public function searchPages(string $keyword, ?string $locale = null): Collection;

    /**
     * Get breadcrumbs for a specific page.
     *
     * @return array<int, array{name:string,slug:string}>
     */
    public function getBreadcrumbs(Page $page): array;

    /**
     * Get related pages for a specific page.
     */
    public function getRelatedPages(Page $page, int $limit = 5): Collection;

    /**
     * Get all published pages.
     */
    public function getAllPublishedPages(): Collection;

    /**
     * Get a category by its slug, optionally filtered by locale.
     */
    public function getCategoryBySlug(string $slug, ?string $locale = null): ?PageCategory;
}

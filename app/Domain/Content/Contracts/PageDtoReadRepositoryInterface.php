<?php

namespace App\Domain\Content\Contracts;

use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageCategoryDTO;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface PageDtoReadRepositoryInterface
{
    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?PageDTO;
    public function firstPublished(): ?PageDTO;
    public function findPublishedBySlug(string $slug, string $locale): ?PageDTO;
    public function findPublishedById(int $id): ?PageDTO;
    public function findPublishedBySlugInLocales(string $slug, array $locales, ?string $skipLocale = null): ?PageDTO;
    /** @return array<int, PageDTO> */
    public function getRootNavigationPages(): array;
    /** @return array<int, PageDTO> */
    public function getPublishedByCategory(int $categoryId): array;
    public function findAnyById(int $id): ?PageDTO;
    /** @return array<int, PageCategoryDTO> */
    public function getCategoriesWithPublishedPages(): array;
    /** @return array<int, PageDTO> */
    public function searchPublished(string $keyword, string $locale): array;
    /** @return array<int, PageDTO> */
    public function getRelatedByCategory(int $categoryId, int $excludePageId, int $limit): array;
    /** @return array<int, PageDTO> */
    public function getAllPublishedWithCategory(): array;
    public function findCategoryBySlug(string $slug, string $locale): ?PageCategoryDTO;
}

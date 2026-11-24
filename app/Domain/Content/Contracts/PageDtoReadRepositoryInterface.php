<?php

namespace App\Domain\Content\Contracts;

use App\Domain\Content\DTO\PageDTO;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface PageDtoReadRepositoryInterface
{
    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?PageDTO;

    public function firstPublished(): ?PageDTO;

    public function findPublishedBySlug(string $slug, string $locale): ?PageDTO;

    public function findPublishedById(int $id): ?PageDTO;

    /** @return list<PageDTO> by sort order, empty when no category has the slug */
    public function getPublishedByCategorySlug(string $categorySlug, string $locale): array;

    public function findPublishedBySlugInLocales(
        string $slug,
        array $locales,
        ?string $skipLocale = null,
    ): ?PageDTO;
}

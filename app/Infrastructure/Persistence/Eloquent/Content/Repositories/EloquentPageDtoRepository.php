<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Repositories;

use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface;
use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageCategoryDTO;
use App\Domain\Content\DTO\PageWriteData;
use App\Infrastructure\Persistence\Eloquent\Content\Mappers\EloquentPageMapper as Mapper;
use App\Models\Page;

/**
 * DTO read repository implemented over Eloquent models.
 *
 * Responsibilities:
 * - Delegates queries to EloquentPageRepository (model-based repo)
 * - Maps models to domain DTOs via EloquentPageMapper
 * - Intended for Domain services to avoid Eloquent dependencies
 */
class EloquentPageDtoRepository implements PageDtoReadRepositoryInterface, PageDtoWriteRepositoryInterface
{
    public function __construct(private readonly EloquentPageRepository $repo) {}

    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?PageDTO
    { $m = $this->repo->findHomepageBySlugs($preferredSlugs, $locale); return $m ? Mapper::toPageDTO($m) : null; }

    public function firstPublished(): ?PageDTO
    { $m = $this->repo->firstPublished(); return $m ? Mapper::toPageDTO($m) : null; }

    public function findPublishedBySlug(string $slug, string $locale): ?PageDTO
    { $m = $this->repo->findPublishedBySlug($slug, $locale); return $m ? Mapper::toPageDTO($m) : null; }

    public function findPublishedById(int $id): ?PageDTO
    { $m = $this->repo->findPublishedById($id); return $m ? Mapper::toPageDTO($m) : null; }

    public function findPublishedBySlugInLocales(string $slug, array $locales, ?string $skipLocale = null): ?PageDTO
    { $m = $this->repo->findPublishedBySlugInLocales($slug, $locales, $skipLocale); return $m ? Mapper::toPageDTO($m) : null; }

    public function getRootNavigationPages(): array
    { return $this->repo->getRootNavigationPages()->map(fn($p) => Mapper::toPageDTO($p))->toArray(); }

    public function getPublishedByCategory(int $categoryId): array
    { return $this->repo->getPublishedByCategory($categoryId)->map(fn($p) => Mapper::toPageDTO($p))->toArray(); }

    public function findAnyById(int $id): ?PageDTO
    { $m = $this->repo->findAnyById($id); return $m ? Mapper::toPageDTO($m) : null; }

    public function getCategoriesWithPublishedPages(): array
    { return $this->repo->getCategoriesWithPublishedPages()->map(fn($c) => Mapper::toCategoryDTO($c))->toArray(); }

    public function searchPublished(string $keyword, string $locale): array
    { return $this->repo->searchPublished($keyword, $locale)->map(fn($p) => Mapper::toPageDTO($p))->toArray(); }

    public function getRelatedByCategory(int $categoryId, int $excludePageId, int $limit): array
    { return $this->repo->getRelatedByCategory($categoryId, $excludePageId, $limit)->map(fn($p) => Mapper::toPageDTO($p))->toArray(); }

    public function getAllPublishedWithCategory(): array
    { return $this->repo->getAllPublishedWithCategory()->map(fn($p) => Mapper::toPageDTO($p))->toArray(); }

    public function findCategoryBySlug(string $slug, string $locale): ?PageCategoryDTO
    { $m = $this->repo->findCategoryBySlug($slug, $locale); return $m ? Mapper::toCategoryDTO($m) : null; }
    
    public function create(PageWriteData $data): PageDTO
    {
        $attributes = $data->toModelAttributes();
        $m = $this->repo->create($attributes);
        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function updateById(int $id, PageWriteData $data): ?PageDTO
    {
        $attributes = $data->toModelAttributes();
        $m = $this->repo->updateById($id, $attributes);
        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function deleteById(int $id): bool
    {
        return $this->repo->deleteById($id);
    }

    public function publish(int $id): bool
    {
        return $this->updateById($id, new PageWriteData(published: true)) !== null;
    }

    public function unpublish(int $id): bool
    {
        return $this->updateById($id, new PageWriteData(published: false)) !== null;
    }

    public function reorder(int $id, int $sortOrder): bool
    {
        return $this->updateById($id, new PageWriteData(sortOrder: $sortOrder)) !== null;
    }
}

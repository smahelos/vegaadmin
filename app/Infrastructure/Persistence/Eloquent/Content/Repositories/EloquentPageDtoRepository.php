<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Repositories;

use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface;
use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageWriteData;
use App\Infrastructure\Persistence\Eloquent\Content\Mappers\EloquentPageMapper as Mapper;

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
    {
        $m = $this->repo->findHomepageBySlugs($preferredSlugs, $locale);

        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function firstPublished(): ?PageDTO
    {
        $m = $this->repo->firstPublished();

        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function findPublishedBySlug(string $slug, string $locale): ?PageDTO
    {
        $m = $this->repo->findPublishedBySlug($slug, $locale);

        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function findPublishedById(int $id): ?PageDTO
    {
        $m = $this->repo->findPublishedById($id);

        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function getPublishedByCategorySlug(string $categorySlug, string $locale): array
    {
        $category = $this->repo->findCategoryBySlug($categorySlug, $locale);
        if (!$category) {
            return [];
        }

        $pages = $this->repo->getPublishedByCategory($category->id);
        $dtos = [];
        foreach ($pages as $page) {
            $dtos[] = Mapper::toPageDTO($page);
        }

        return $dtos;
    }

    public function findPublishedBySlugInLocales(
        string $slug,
        array $locales,
        ?string $skipLocale = null,
    ): ?PageDTO {
        $m = $this->repo->findPublishedBySlugInLocales(
            $slug,
            $locales,
            $skipLocale,
        );

        return $m ? Mapper::toPageDTO($m) : null;
    }

    public function create(PageWriteData $data): PageDTO
    {
        $attributes = $data->toModelAttributes();
        $m = $this->repo->create($attributes);

        return $m ? Mapper::toPageDTO($m) : null;
    }
}

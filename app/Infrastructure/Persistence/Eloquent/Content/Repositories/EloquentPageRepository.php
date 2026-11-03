<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Repositories;

use App\Application\Content\Contracts\PageReadRepositoryInterface;
use App\Application\Content\Contracts\PageWriteRepositoryInterface;
use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent implementation of Page read/write repositories.
 */
class EloquentPageRepository implements PageReadRepositoryInterface, PageWriteRepositoryInterface
{
    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?Page
    {
        foreach ($preferredSlugs as $slug) {
            $query = Page::published();
            $query->whereJsonContains("slug->{$locale}", $slug);
            $homepage = $query->first();
            if ($homepage) {
                return $homepage;
            }
        }

        return null;
    }

    public function firstPublished(): ?Page
    {
        $query = Page::published();
        $query->orderBy('sort_order');
        $query->orderBy('created_at');

        return $query->first();
    }

    public function findPublishedBySlug(string $slug, string $locale): ?Page
    {
        $query = Page::published();
        $query->whereJsonContains("slug->{$locale}", $slug);
        $query->with([
            'category',
            'parent',
            'children',
        ]);

        return $query->first();
    }

    public function findPublishedById(int $id): ?Page
    {
        $query = Page::published();
        $query->where('id', $id);
        $query->with([
            'category',
            'parent',
            'children',
        ]);

        return $query->first();
    }

    public function findPublishedBySlugInLocales(
        string $slug,
        array $locales,
        ?string $skipLocale = null,
    ): ?Page {
        foreach ($locales as $searchLocale) {
            if ($skipLocale !== null && $searchLocale === $skipLocale) {
                continue;
            }
            $query = Page::published();
            $query->whereJsonContains("slug->{$searchLocale}", $slug);
            $query->with([
                'category',
                'parent',
                'children',
            ]);
            $page = $query->first();
            if ($page) {
                return $page;
            }
        }

        return null;
    }

    public function getRootNavigationPages(): Collection
    {
        $query = Page::published();
        $query = $query->rootPages();
        $query->with(
            ['children' => function ($q): void {
                $q = $q->published();
                $q->orderBy('sort_order');
            }],
        );
        $query->orderBy('sort_order');

        return $query->get();
    }

    public function getPublishedByCategory(int $categoryId): Collection
    {
        $query = Page::published();
        $query = $query->byCategory($categoryId);
        $query->orderBy('sort_order');

        return $query->get();
    }

    public function findAnyById(int $id): ?Page
    {
        $query = Page::where('id', $id);

        return $query->first();
    }

    public function getCategoriesWithPublishedPages(): Collection
    {
        $query = PageCategory::whereHas('pages', fn ($q) => $q->published());
        $query->with(
            ['pages' => function (HasMany $q): void {
                $q = $q->published();
                $q->orderBy('sort_order');
            }],
        );

        return $query->get();
    }

    public function searchPublished(string $keyword, string $locale): Collection
    {
        $query = Page::published();
        $query->where(
            function ($q) use ($keyword, $locale): void {
                $q->whereRaw("JSON_EXTRACT(name, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"]);
                $q->orWhereRaw("JSON_EXTRACT(description, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"]);
                $q->orWhereRaw("JSON_EXTRACT(content, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"]);
            },
        );
        $query->orderBy('sort_order');

        return $query->get();
    }

    public function getRelatedByCategory(
        int $categoryId,
        int $excludePageId,
        int $limit,
    ): Collection {
        $query = Page::published();
        $query = $query->byCategory($categoryId);
        $query->where(
            'id',
            '!=',
            $excludePageId,
        );
        $query->orderBy('sort_order');
        $query->limit($limit);

        return $query->get();
    }

    public function getAllPublishedWithCategory(): Collection
    {
        $query = Page::published();
        $query->with(['category']);
        $query->orderBy('sort_order');
        $query->orderBy('name');

        return $query->get();
    }

    public function findCategoryBySlug(string $slug, string $locale): ?PageCategory
    {
        $query = PageCategory::whereJsonContains("slug->{$locale}", $slug);

        return $query->first();
    }

    // public function updateById(int $id, array $attributes): bool
    // {
    //     return (bool) Page::where('id', $id)->update($attributes);
    // }

    // public function deleteById(int $id): bool
    // {
    //     return (bool) Page::where('id', $id)->delete();
    // }

    public function create(array $data): Page
    {
        $model = new Page($data);
        $model->save();

        // Load relations to keep consistency with read DTOs (e.g., category)
        return $model->load(['category', 'children']);
    }
}

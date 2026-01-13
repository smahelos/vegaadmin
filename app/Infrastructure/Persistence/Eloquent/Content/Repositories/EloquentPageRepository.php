<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Repositories;

use App\Application\Content\Contracts\PageReadRepositoryInterface;
use App\Application\Content\Contracts\PageWriteRepositoryInterface;
use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of Page read/write repositories.
 */
class EloquentPageRepository implements PageReadRepositoryInterface, PageWriteRepositoryInterface
{
    public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?Page
    {
        foreach ($preferredSlugs as $slug) {
            $homepage = Page::published()->whereJsonContains("slug->{$locale}", $slug)->first();
            if ($homepage) { return $homepage; }
        }
        return null;
    }

    public function firstPublished(): ?Page
    {
        return Page::published()->orderBy('sort_order')->orderBy('created_at')->first();
    }

    public function findPublishedBySlug(string $slug, string $locale): ?Page
    {
        return Page::published()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->with(['category','parent','children'])
            ->first();
    }

    public function findPublishedById(int $id): ?Page
    {
        return Page::published()->where('id', $id)->with(['category','parent','children'])->first();
    }

    public function findPublishedBySlugInLocales(string $slug, array $locales, ?string $skipLocale = null): ?Page
    {
        foreach ($locales as $searchLocale) {
            if ($skipLocale !== null && $searchLocale === $skipLocale) { continue; }
            $page = Page::published()->whereJsonContains("slug->{$searchLocale}", $slug)->with(['category','parent','children'])->first();
            if ($page) { return $page; }
        }
        return null;
    }

    public function getRootNavigationPages(): Collection
    {
        return Page::published()
            ->rootPages()
            ->with(['children' => function ($q) { $q->published()->orderBy('sort_order'); }])
            ->orderBy('sort_order')
            ->get();
    }

    public function getPublishedByCategory(int $categoryId): Collection
    {
        return Page::published()->byCategory($categoryId)->orderBy('sort_order')->get();
    }

    public function findAnyById(int $id): ?Page
    {
        return Page::where('id', $id)->first();
    }

    public function getCategoriesWithPublishedPages(): Collection
    {
        return PageCategory::whereHas('pages', fn($q) => $q->published())
            ->with(['pages' => fn($q) => $q->published()->orderBy('sort_order')])
            ->get();
    }

    public function searchPublished(string $keyword, string $locale): Collection
    {
        return Page::published()->where(function ($q) use ($keyword, $locale) {
            $q->whereRaw("JSON_EXTRACT(name, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"])
              ->orWhereRaw("JSON_EXTRACT(description, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"])
              ->orWhereRaw("JSON_EXTRACT(content, '$.\"{$locale}\"') LIKE ?", ["%{$keyword}%"]);
        })->orderBy('sort_order')->get();
    }

    public function getRelatedByCategory(int $categoryId, int $excludePageId, int $limit): Collection
    {
        return Page::published()
            ->byCategory($categoryId)
            ->where('id', '!=', $excludePageId)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function getAllPublishedWithCategory(): Collection
    {
        return Page::published()->with(['category'])->orderBy('sort_order')->orderBy('name')->get();
    }

    public function findCategoryBySlug(string $slug, string $locale): ?PageCategory
    {
        return PageCategory::whereJsonContains("slug->{$locale}", $slug)->first();
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

    public function updateById(int $id, array $data): ?Page
    {
        $model = Page::query()->find($id);
        if (!$model) { return null; }
        if (!empty($data)) {
            $model->fill($data);
            $model->save();
        }
        // Load relations to keep consistency with read DTOs (e.g., category)
        return $model->load(['category', 'children']);
    }

    public function deleteById(int $id): bool
    {
        $model = Page::query()->find($id);
        if (!$model) { return false; }
        return (bool) $model->delete();
    }
}

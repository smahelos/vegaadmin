<?php

namespace App\Domain\Content\Services;

use App\Domain\User\Contracts\Locale;
use App\Domain\Content\Contracts\PageServiceInterface;
use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageCategoryDTO;

class PageService implements PageServiceInterface
{
    public function __construct(
        private readonly PageDtoReadRepositoryInterface $pages,
        private readonly Locale $locale
    )
    {
    }
    /**
     * Get the homepage based on predefined slugs or fallback to first published page.
     *
     * @param string|null $locale Locale to use for slug matching
     * @return PageDTO|null
     */
    public function getHomepage(?string $locale = null): ?PageDTO
    {
        $locale = $locale ?? $this->locale->getLocale();
        $homeSlugs = ['home', 'homepage', 'uvod'];
        $homepage = $this->pages->findHomepageBySlugs($homeSlugs, $locale);
        if ($homepage) { return $homepage; }
        return $this->pages->firstPublished();
    }

    /**
     * Get page by slug or ID, searching across all available locales.
     *
     * @param string $slug Slug or ID of the page
     * @param string|null $locale Locale to use for slug matching
     * @return PageDTO|null
     */
    public function getPageBySlug(string $slug, ?string $locale = null): ?PageDTO
    {
        $locale = $locale ?? $this->locale->getLocale();
        $page = $this->pages->findPublishedBySlug($slug, $locale);
        if ($page) { return $page; }
        if (is_numeric($slug)) {
            $page = $this->pages->findPublishedById((int)$slug);
            if ($page) { return $page; }
        }
        $availableLocales = $this->locale->getAvailableLocales();
        return $this->pages->findPublishedBySlugInLocales($slug, $availableLocales, $locale);
    }

}

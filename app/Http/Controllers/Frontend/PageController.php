<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Content\Services\PageApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class PageController extends Controller
{
    // Czech slugs that PageContentSeeder creates for the homepage sections
    private const FEATURE_CATEGORY_SLUG = 'funkce';

    private const PRICE_PAGE_SLUG = 'cenik';

    public function __construct(
        private readonly PageApplicationService $pageService,
    ) {}

    /**
     * Display homepage with custom page content
     */
    public function homepage(): View
    {
        $locale = App::getLocale();
        $page = $this->pageService->getHomepage($locale);

        // If no published pages exist, show a default message
        if (!$page) {
            return view('frontend.pages.homepage-empty');
        }

        // Get navigation pages for menu
        $navigationPages = $this->pageService->getPagesForNavigation();

        $featurePages = $this->pageService->getPagesByCategorySlug(
            self::FEATURE_CATEGORY_SLUG,
            'cs',
        );
        $pricePage = $this->pageService->getPageBySlug(self::PRICE_PAGE_SLUG, 'cs');

        return view(
            'frontend.pages.homepage',
            compact(
                'page',
                'locale',
                'navigationPages',
                'featurePages',
                'pricePage',
            ),
        );
    }

    /**
     * Display all published pages
     */
    public function index(): View
    {
        $pages = $this->pageService->getAllPublishedPages();
        $categoriesWithPages = $this->pageService->getCategoriesWithPages();
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.index',
            compact(
                'pages',
                'categoriesWithPages',
                'navigationPages',
            ),
        );
    }

    /**
     * Display specific page by slug
     */
    public function show(string $locale, string $slug): View|RedirectResponse
    {
        // Set application locale from URL parameter
        App::setLocale($locale);

        $page = $this->pageService->getPageBySlug($slug, $locale);

        if (!$page) {
            abort(
                404,
                __('pages.page_not_found'),
            );
        }

        // Check if the current URL slug matches the localized slug
        // If not, redirect to the correct URL for the current locale
        $localizedSlug = $page->slug[$locale] ?? null;
        if ($localizedSlug !== $slug) {
            $redirector = redirect();

            return $redirector->route('frontend.pages.show', [
                'locale' => $locale,
                'slug' => $localizedSlug,
            ]);
        }

        // Get breadcrumbs
        $breadcrumbs = $this->pageService->getBreadcrumbs($page);

        // Get related pages
        $relatedPages = $this->pageService->getRelatedPages($page, 3);

        // Get navigation pages for menu
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.show',
            compact(
                'page',
                'breadcrumbs',
                'relatedPages',
                'navigationPages',
                'locale',
            ),
        );
    }

    /**
     * Display pages by category slug
     */
    public function categoryBySlug(string $locale, string $slug): View
    {
        // Set application locale from URL parameter
        App::setLocale($locale);

        $category = $this->pageService->getCategoryBySlug($slug, $locale);

        if (!$category) {
            abort(
                404,
                __('pages.category_not_found'),
            );
        }

        $pages = $this->pageService->getPagesByCategory($category->id);
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.category',
            compact(
                'pages',
                'category',
                'navigationPages',
                'locale',
            ),
        );
    }

    /**
     * Show pages for a specific category by ID
     */
    public function category(string $locale, string $categoryId): View
    {
        // Set application locale from URL parameter
        App::setLocale($locale);

        $pages = $this->pageService->getPagesByCategory((int) $categoryId);

        if ($pages->isEmpty()) {
            abort(
                404,
                __('pages.category_not_found'),
            );
        }

        $category = $pages->first()->category;
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.category',
            compact(
                'pages',
                'category',
                'navigationPages',
                'locale',
            ),
        );
    }

    /**
     * Search pages
     */
    public function search(Request $request, ?string $locale = null): View
    {
        $locale = $locale ?? App::getLocale();
        // Set application locale
        App::setLocale($locale);

        $keyword = $request->get('q', '');
        $pages = collect();

        if (strlen($keyword) >= 3) {
            $pages = $this->pageService->searchPages($keyword, $locale);
        }

        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.search',
            compact(
                'pages',
                'keyword',
                'navigationPages',
                'locale',
            ),
        );
    }

    /**
     * Display all pages (sitemap)
     */
    public function sitemap(?string $locale = null): View
    {
        // Set application locale from URL parameter or use current locale
        $locale = $locale ?? App::getLocale();
        App::setLocale($locale);

        $categoriesWithPages = $this->pageService->getCategoriesWithPages();
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view(
            'frontend.pages.sitemap',
            compact(
                'categoriesWithPages',
                'navigationPages',
                'locale',
            ),
        );
    }
}

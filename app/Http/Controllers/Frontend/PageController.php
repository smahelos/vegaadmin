<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Application\Content\Contracts\PageApplicationServiceInterface as PageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function __construct(
        private PageServiceInterface $pageService
    ) {}

    /**
     * Display homepage with custom page content
     *
     * @return \Illuminate\View\View
     */
    public function homepage(): View
    {
        $locale = app()->getLocale();
        $page = $this->pageService->getHomepage($locale);
        
        // If no published pages exist, show a default message
        if (!$page) {
            return view('frontend.pages.homepage-empty');
        }

        // Get navigation pages for menu
        $navigationPages = $this->pageService->getPagesForNavigation();

        // Get Feature pages for homepage
        // TODO: This should be dynamic, currently hardcoded to category ID 4
        $featurePages = $this->pageService->getPagesByCategory(4);

        // Get Prices
        // TODO: This should be dynamic, currently hardcoded to page ID 11
        $pricePage = $this->pageService->getPageContentById(11);

        return view('frontend.pages.homepage', compact('page', 'locale', 'navigationPages', 'featurePages', 'pricePage'));
    }

    /**
     * Display all published pages
     * 
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $pages = $this->pageService->getAllPublishedPages();
        $categoriesWithPages = $this->pageService->getCategoriesWithPages();
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.index', compact('pages', 'categoriesWithPages', 'navigationPages'));
    }

    /**
     * Display specific page by slug
     * 
     * @param string $locale
     * @param string $slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $locale, string $slug): View|RedirectResponse
    {
        // Set application locale from URL parameter
        app()->setLocale($locale);
        
        $page = $this->pageService->getPageBySlug($slug, $locale);
        
        if (!$page) {
            abort(404, __('pages.page_not_found'));
        }
        
        // Check if the current URL slug matches the localized slug
        // If not, redirect to the correct URL for the current locale
        $localizedSlug = $page->slug[$locale] ?? null;
        if ($localizedSlug !== $slug) {
            return redirect()->route('frontend.pages.show', [
                'locale' => $locale,
                'slug' => $localizedSlug
            ]);
        }

        // Get breadcrumbs
        $breadcrumbs = $this->pageService->getBreadcrumbs($page);
        
        // Get related pages
        $relatedPages = $this->pageService->getRelatedPages($page, 3);
        
        // Get navigation pages for menu
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.show', compact(
            'page', 
            'breadcrumbs', 
            'relatedPages', 
            'navigationPages',
            'locale'
        ));
    }

    /**
     * Display pages by category slug
     * 
     * @param string $locale
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function categoryBySlug(string $locale, string $slug): View
    {
        // Set application locale from URL parameter
        app()->setLocale($locale);
        
        $category = $this->pageService->getCategoryBySlug($slug, $locale);
        
        if (!$category) {
            abort(404, __('pages.category_not_found'));
        }

        $pages = $this->pageService->getPagesByCategory($category->id);
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.category', compact('pages', 'category', 'navigationPages', 'locale'));
    }

    /**
     * Show pages for a specific category by ID
     *
     * @param string $categoryId
     * @param string|null $locale
     * @return \Illuminate\View\View
     */
    public function category(string $categoryId, ?string $locale = null): View
    {
        // Set application locale from URL parameter or use current locale
        $locale = $locale ?? app()->getLocale();
        app()->setLocale($locale);
        
        $pages = $this->pageService->getPagesByCategory((int)$categoryId);

        if ($pages->isEmpty()) {
            abort(404, __('pages.category_not_found'));
        }

        $category = $pages->first()->category;
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.category', compact('pages', 'category', 'navigationPages', 'locale'));
    }
    
    /**
     * Search pages
     * 
     * @param \Illuminate\Http\Request $request
     * @param string|null $locale
     * @return \Illuminate\View\View
     */
    public function search(Request $request, ?string $locale = null): View
    {
        $locale = $locale ?? app()->getLocale();
        // Set application locale
        app()->setLocale($locale);
        
        $keyword = $request->get('q', '');
        $pages = collect();
        
        if (strlen($keyword) >= 3) {
            $pages = $this->pageService->searchPages($keyword, $locale);
        }

        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.search', compact('pages', 'keyword', 'navigationPages', 'locale'));
    }

    /**
     * Display all pages (sitemap)
     * 
     * @param string|null $locale
     * @return \Illuminate\View\View
     */
    public function sitemap(?string $locale = null): View
    {
        // Set application locale from URL parameter or use current locale
        $locale = $locale ?? app()->getLocale();
        app()->setLocale($locale);
        
        $categoriesWithPages = $this->pageService->getCategoriesWithPages();
        $navigationPages = $this->pageService->getPagesForNavigation();

        return view('frontend.pages.sitemap', compact('categoriesWithPages', 'navigationPages', 'locale'));
    }
}

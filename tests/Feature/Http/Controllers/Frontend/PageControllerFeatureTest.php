<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PageControllerFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The guest invoice link of the empty homepage needs a locale for route()
        URL::defaults(['locale' => 'cs']);

        // The pages read the whole pages table; start without the seeded content pages
        $pageQuery = Page::query();
        $pageQuery->delete();
    }

    /** @param array<string, mixed> $attributes */
    private function publishedPage(array $attributes = []): Page
    {
        $pageFactory = Page::factory();
        $publishedFactory = $pageFactory->published();

        return $publishedFactory->create(
            $attributes + [
                'name' => ['cs' => 'Stránka', 'en' => 'Page'],
                'slug' => ['cs' => 'stranka-' . uniqid(), 'en' => 'page-' . uniqid()],
                'description' => ['cs' => 'Popis', 'en' => 'Description'],
                'content' => ['cs' => 'Obsah', 'en' => 'Content'],
                'publishing_end' => null,
            ],
        );
    }

    #[Test]
    public function homepage_renders_empty_when_no_page(): void
    {
        $response = $this->get(
            route('home', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertViewIs('frontend.pages.homepage-empty');
    }

    #[Test]
    public function homepage_renders_with_page_and_related_sections(): void
    {
        $page = $this->publishedPage(['slug' => ['cs' => 'uvod', 'en' => 'home']]);
        $response = $this->get(
            route('home', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.homepage');
        $assertionResponse = $assertionResponse->assertViewHasAll(
            [
                'navigationPages',
                'featurePages',
                'pricePage',
            ],
        );
        $assertionResponse->assertViewHas(
            'page',
            fn (Page $shown): bool => $shown->is($page),
        );
    }

    #[Test]
    public function index_displays_published_pages_and_categories(): void
    {
        $page = $this->publishedPage();
        $response = $this->get(
            route('frontend.pages.index', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.index');
        $assertionResponse = $assertionResponse->assertViewHasAll([
            'categoriesWithPages',
            'navigationPages',
        ]);
        $assertionResponse->assertViewHas(
            'pages',
            fn (Collection $pages): bool => $pages->contains($page),
        );
    }

    #[Test]
    public function show_displays_page(): void
    {
        $page = $this->publishedPage(['slug' => ['cs' => 'moje-stranka', 'en' => 'my-page']]);
        $response = $this->get(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'moje-stranka']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.show');
        $assertionResponse = $assertionResponse->assertViewHasAll(
            [
                'breadcrumbs',
                'relatedPages',
                'navigationPages',
                'locale',
            ],
        );
        $assertionResponse->assertViewHas(
            'page',
            fn (Page $shown): bool => $shown->is($page),
        );
    }

    #[Test]
    public function show_redirects_when_slug_not_localized(): void
    {
        $this->publishedPage(['slug' => ['cs' => 'lokalni', 'en' => 'english-slug']]);
        $response = $this->get(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'english-slug']),
        );
        $response->assertRedirect(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'lokalni']),
        );
    }

    #[Test]
    public function show_returns_404_when_not_found(): void
    {
        $response = $this->get(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'neexistuje']),
        );
        $response->assertNotFound();
    }

    #[Test]
    public function category_by_slug_displays_pages(): void
    {
        $slug = 'kategorie-' . uniqid();
        $categoryFactory = PageCategory::factory();
        $category = $categoryFactory->create(
            [
                'name' => ['cs' => 'Kategorie', 'en' => 'Category'],
                'slug' => ['cs' => $slug, 'en' => 'category-' . uniqid()],
            ],
        );
        $page = $this->publishedPage(['category_id' => $category->id]);
        $response = $this->get(
            route('frontend.pages.category.slug', ['locale' => 'cs', 'slug' => $slug]),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.category');
        $assertionResponse->assertViewHas(
            'pages',
            fn (Collection $pages): bool => $pages->contains($page),
        );
    }

    #[Test]
    public function category_by_slug_404_when_not_found(): void
    {
        $response = $this->get(
            route('frontend.pages.category.slug', ['locale' => 'cs', 'slug' => 'nic']),
        );
        $response->assertNotFound();
    }

    #[Test]
    public function category_by_id_displays_pages(): void
    {
        $page = $this->publishedPage();
        $response = $this->get(
            route('frontend.pages.category', ['locale' => 'cs', 'categoryId' => $page->category_id]),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.category');
        $assertionResponse->assertViewHas(
            'pages',
            fn (Collection $pages): bool => $pages->contains($page),
        );
    }

    #[Test]
    public function category_by_id_404_when_empty(): void
    {
        $categoryFactory = PageCategory::factory();
        $category = $categoryFactory->create();
        $response = $this->get(
            route('frontend.pages.category', ['locale' => 'cs', 'categoryId' => $category->id]),
        );
        $response->assertNotFound();
    }

    #[Test]
    public function search_requires_min_length(): void
    {
        $this->publishedPage(['name' => ['cs' => 'abeceda', 'en' => 'alphabet']]);
        $response = $this->get(
            route('frontend.pages.search', ['locale' => 'cs', 'q' => 'ab']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.search');
        $assertionResponse->assertViewHas(
            'pages',
            fn (Collection $pages): bool => $pages->isEmpty(),
        );
    }

    #[Test]
    public function search_returns_results_when_long_enough(): void
    {
        $page = $this->publishedPage(
            ['name' => ['cs' => 'Testování faktur', 'en' => 'Invoice testing']],
        );
        $this->publishedPage();
        $response = $this->get(
            route('frontend.pages.search', ['locale' => 'cs', 'q' => 'Testování']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.search');
        $assertionResponse->assertViewHas(
            'pages',
            fn (Collection $pages): bool => $pages->count() === 1 && $pages->contains($page),
        );
    }

    #[Test]
    public function sitemap_displays_categories_with_pages(): void
    {
        $page = $this->publishedPage();
        $response = $this->get(
            route('frontend.pages.sitemap', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.sitemap');
        $assertionResponse->assertViewHas(
            'categoriesWithPages',
            fn (Collection $categories): bool => $categories->contains('id', $page->category_id),
        );
    }
}

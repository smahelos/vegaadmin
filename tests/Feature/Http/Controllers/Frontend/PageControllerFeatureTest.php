<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Content\Contracts\PageApplicationServiceInterface as PageServiceInterface;
use App\Models\Page;
use App\Models\PageCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for PageController.
 * Covers homepage, index, show (found, localized redirect, 404), category (by slug & id), search (min length logic), sitemap.
 */
class PageControllerFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Nastav výchozí parametr locale pro generování route odkazů ve view (homepage-empty odkazuje na guest invoice route)
        URL::defaults(['locale' => 'cs']);
    }

    private function mockPage(array $attrs = []): Page
    {
        $page = new Page;
        $page->id = $attrs['id'] ?? 1;
        $page->name = $attrs['name'] ?? ['cs' => 'Stranka', 'en' => 'Page'];
        $page->slug = $attrs['slug'] ?? ['cs' => 'stranka', 'en' => 'page'];
        $page->description = $attrs['description'] ?? ['cs' => 'Popis', 'en' => 'Desc'];
        $page->content = $attrs['content'] ?? ['cs' => 'Obsah', 'en' => 'Content'];
        $page->meta_title = $attrs['meta_title'] ?? ['cs' => 'MT', 'en' => 'MT'];
        $page->meta_description = $attrs['meta_description'] ?? ['cs' => 'MD', 'en' => 'MD'];
        $page->category_id = $attrs['category_id'] ?? 1;
        $page->published = true;
        $page->created_at = Carbon::now();

        return $page;
    }

    #[Test]
    public function homepage_renders_empty_when_no_page(): void
    {
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getHomepage');
        $expectation->once();
        $expectation->andReturn(null);
        $response = $this->get(
            route('home', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertViewIs('frontend.pages.homepage-empty');
    }

    #[Test]
    public function homepage_renders_with_page_and_related_sections(): void
    {
        $page = $this->mockPage(['id' => 10]);
        $feature = $this->mockPage(
            ['id' => 11, 'slug' => ['cs' => 'feature', 'en' => 'feature']],
        );
        $price = $this->mockPage(['id' => 12]);
        $mock = $this->mock(PageServiceInterface::class);
        $expectation = $mock->shouldReceive('getHomepage');
        $expectation->once();
        $expectation->andReturn($page);
        $expectation = $mock->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $expectation = $mock->shouldReceive('getPagesByCategory');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$feature]),
        );
        $expectation = $mock->shouldReceive('getPageContentById');
        $expectation->once();
        $expectation->andReturn($price);
        $response = $this->get(
            route('home', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.homepage');
        $assertionResponse->assertViewHasAll(
            [
                'page',
                'navigationPages',
                'featurePages',
                'pricePage',
            ],
        );
    }

    #[Test]
    public function index_displays_published_pages_and_categories(): void
    {
        $page = $this->mockPage();
        $mock = $this->mock(PageServiceInterface::class);
        $expectation = $mock->shouldReceive('getAllPublishedPages');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $expectation = $mock->shouldReceive('getCategoriesWithPages');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $expectation = $mock->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $response = $this->get(
            route('frontend.pages.index', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.index');
        $assertionResponse->assertViewHasAll([
            'pages',
            'categoriesWithPages',
            'navigationPages',
        ]);
    }

    #[Test]
    public function show_displays_page(): void
    {
        $page = $this->mockPage(['slug' => ['cs' => 'moje-stranka', 'en' => 'my-page']]);
        $mock = $this->mock(PageServiceInterface::class);
        $expectation = $mock->shouldReceive('getPageBySlug');
        $expectation->once();
        $expectation->andReturn($page);
        $expectation = $mock->shouldReceive('getBreadcrumbs');
        $expectation->once();
        $expectation->andReturn([['name' => 'Root', 'slug' => 'root']]);
        $expectation = $mock->shouldReceive('getRelatedPages');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $expectation = $mock->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $response = $this->get(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'moje-stranka']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.show');
        $assertionResponse->assertViewHasAll(
            [
                'page',
                'breadcrumbs',
                'relatedPages',
                'navigationPages',
                'locale',
            ],
        );
    }

    #[Test]
    public function show_redirects_when_slug_not_localized(): void
    {
        $page = $this->mockPage(['slug' => ['cs' => 'lokalni', 'en' => 'english-slug']]);
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getPageBySlug');
        $expectation->once();
        $expectation->andReturn($page);
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
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getPageBySlug');
        $expectation->once();
        $expectation->andReturn(null);
        $response = $this->get(
            route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'neexistuje']),
        );
        $response->assertStatus(404);
    }

    #[Test]
    public function category_by_slug_displays_pages(): void
    {
        $category = new PageCategory;
        $category->id = 5;
        $category->name = ['cs' => 'Kategorie', 'en' => 'Category'];
        $category->slug = ['cs' => 'kategorie', 'en' => 'category'];
        $category->description = ['cs' => 'Popis', 'en' => 'Desc'];
        $page = $this->mockPage(['category_id' => 5]);
        // navigation page also needs a category for blade reference
        $page->category = new PageCategory;
        $page->category->id = 6;
        $page->category->name = ['cs' => 'Jina', 'en' => 'Other'];
        $page->category->slug = ['cs' => 'jina', 'en' => 'other'];
        $mock = $this->mock(PageServiceInterface::class);
        $expectation = $mock->shouldReceive('getCategoryBySlug');
        $expectation->once();
        $expectation->andReturn($category);
        $expectation = $mock->shouldReceive('getPagesByCategory');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $expectation = $mock->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $response = $this->get(
            route('frontend.pages.category.slug', ['locale' => 'cs', 'slug' => 'kategorie']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertViewIs('frontend.pages.category');
    }

    #[Test]
    public function category_by_slug_404_when_not_found(): void
    {
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getCategoryBySlug');
        $expectation->once();
        $expectation->andReturn(null);
        $response = $this->get(
            route('frontend.pages.category.slug', ['locale' => 'cs', 'slug' => 'nic']),
        );
        $response->assertStatus(404);
    }

    #[Test]
    public function category_by_id_displays_pages(): void
    {
        $page = $this->mockPage(['category_id' => 7]);
        $cat = new PageCategory;
        $cat->name = ['cs' => 'Kategorie', 'en' => 'Category'];
        $cat->slug = ['cs' => 'kategorie-7', 'en' => 'category-7'];
        $page->category = $cat;
        $mock = $this->mock(PageServiceInterface::class);
        $expectation = $mock->shouldReceive('getPagesByCategory');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $expectation = $mock->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $response = $this->get(
            route('frontend.pages.category', ['locale' => 'cs', 'categoryId' => 7]),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertViewIs('frontend.pages.category');
    }

    #[Test]
    public function category_by_id_404_when_empty(): void
    {
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getPagesByCategory');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $response = $this->get(
            route('frontend.pages.category', ['locale' => 'cs', 'categoryId' => 99]),
        );
        $response->assertStatus(404);
    }

    #[Test]
    public function search_requires_min_length(): void
    {
        $pageService = $this->mock(PageServiceInterface::class);
        $expectation = $pageService->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $response = $this->get(
            route('frontend.pages.search', ['locale' => 'cs', 'q' => 'ab']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.search');
        $assertionResponse->assertViewHas('pages', function ($c) {
            return $c->count() === 0;
        });
    }

    #[Test]
    public function search_returns_results_when_long_enough(): void
    {
        $page = $this->mockPage();
        $mockService = $this->mock(PageServiceInterface::class);
        $expectation = $mockService->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $expectation = $mockService->shouldReceive('searchPages');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([$page]),
        );
        $response = $this->get(
            route('frontend.pages.search', ['locale' => 'cs', 'q' => 'testovani']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertViewIs('frontend.pages.search');
        $assertionResponse->assertViewHas('pages', function ($c) {
            return $c->count() === 1;
        });
    }

    #[Test]
    public function sitemap_displays_categories_with_pages(): void
    {
        $mockService = $this->mock(PageServiceInterface::class);
        $expectation = $mockService->shouldReceive('getCategoriesWithPages');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $expectation = $mockService->shouldReceive('getPagesForNavigation');
        $expectation->once();
        $expectation->andReturn(
            new EloquentCollection([]),
        );
        $response = $this->get(
            route('frontend.pages.sitemap', ['locale' => 'cs']),
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertViewIs('frontend.pages.sitemap');
    }
}

<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Content\Contracts\PageApplicationServiceInterface as PageServiceInterface;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Carbon\Carbon;
use App\Models\PageCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for PageController.
 * Covers homepage, index, show (found, localized redirect, 404), category (by slug & id), search (min length logic), sitemap.
 */
class PageControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Nastav výchozí parametr locale pro generování route odkazů ve view (homepage-empty odkazuje na guest invoice route)
        URL::defaults(['locale' => 'cs']);
    }

    private function mockPage(array $attrs = []): Page
    {
        $page = new Page();
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
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getHomepage')->once()->andReturn(null);
        $response = $this->get(route('home', ['locale' => 'cs']));
        $response->assertOk()->assertViewIs('frontend.pages.homepage-empty');
    }

    #[Test]
    public function homepage_renders_with_page_and_related_sections(): void
    {
        $page = $this->mockPage(['id' => 10]);
        $feature = $this->mockPage(['id' => 11, 'slug' => ['cs'=>'feature','en'=>'feature']]);
        $price = $this->mockPage(['id' => 12]);
    $mock = $this->mock(PageServiceInterface::class);
    $mock->shouldReceive('getHomepage')->once()->andReturn($page);
        $mock->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([$page]));
    $mock->shouldReceive('getPagesByCategory')->once()->andReturn(new EloquentCollection([$feature]));
    $mock->shouldReceive('getPageContentById')->once()->andReturn($price);
        $response = $this->get(route('home', ['locale' => 'cs']));
        $response->assertOk()->assertViewIs('frontend.pages.homepage')
            ->assertViewHasAll(['page','navigationPages','featurePages','pricePage']);
    }

    #[Test]
    public function index_displays_published_pages_and_categories(): void
    {
        $page = $this->mockPage();
    $mock = $this->mock(PageServiceInterface::class);
        $mock->shouldReceive('getAllPublishedPages')->once()->andReturn(new EloquentCollection([$page]));
    $mock->shouldReceive('getCategoriesWithPages')->once()->andReturn(new EloquentCollection([]));
        $mock->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([$page]));
        $response = $this->get(route('frontend.pages.index', ['locale' => 'cs']));
        $response->assertOk()->assertViewIs('frontend.pages.index')
            ->assertViewHasAll(['pages','categoriesWithPages','navigationPages']);
    }

    #[Test]
    public function show_displays_page(): void
    {
        $page = $this->mockPage(['slug' => ['cs'=>'moje-stranka','en'=>'my-page']]);
    $mock = $this->mock(PageServiceInterface::class);
    $mock->shouldReceive('getPageBySlug')->once()->andReturn($page);
    $mock->shouldReceive('getBreadcrumbs')->once()->andReturn([['name'=>'Root','slug'=>'root']]);
    $mock->shouldReceive('getRelatedPages')->once()->andReturn(new EloquentCollection([]));
        $mock->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([$page]));
        $response = $this->get(route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'moje-stranka']));
        $response->assertOk()->assertViewIs('frontend.pages.show')
            ->assertViewHasAll(['page','breadcrumbs','relatedPages','navigationPages','locale']);
    }

    #[Test]
    public function show_redirects_when_slug_not_localized(): void
    {
        $page = $this->mockPage(['slug' => ['cs'=>'lokalni','en'=>'english-slug']]);
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getPageBySlug')->once()->andReturn($page);
        $response = $this->get(route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'english-slug']));
        $response->assertRedirect(route('frontend.pages.show', ['locale'=>'cs','slug'=>'lokalni']));
    }

    #[Test]
    public function show_returns_404_when_not_found(): void
    {
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getPageBySlug')->once()->andReturn(null);
        $response = $this->get(route('frontend.pages.show', ['locale' => 'cs', 'slug' => 'neexistuje']));
        $response->assertStatus(404);
    }

    #[Test]
    public function category_by_slug_displays_pages(): void
    {
    $category = new PageCategory();
    $category->id = 5;
    $category->name = ['cs'=>'Kategorie','en'=>'Category'];
    $category->slug = ['cs'=>'kategorie','en'=>'category'];
    $category->description = ['cs'=>'Popis','en'=>'Desc'];
    $page = $this->mockPage(['category_id'=>5]);
    // navigation page also needs a category for blade reference
    $page->category = new PageCategory();
    $page->category->id = 6;
    $page->category->name = ['cs'=>'Jina','en'=>'Other'];
    $page->category->slug = ['cs'=>'jina','en'=>'other'];
        $mock = $this->mock(PageServiceInterface::class);
        $mock->shouldReceive('getCategoryBySlug')->once()->andReturn($category);
        $mock->shouldReceive('getPagesByCategory')->once()->andReturn(new EloquentCollection([$page]));
        $mock->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([$page]));
        $response = $this->get(route('frontend.pages.category.slug', ['locale'=>'cs','slug'=>'kategorie']));
        $response->assertOk()->assertViewIs('frontend.pages.category');
    }

    #[Test]
    public function category_by_slug_404_when_not_found(): void
    {
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getCategoryBySlug')->once()->andReturn(null);
        $response = $this->get(route('frontend.pages.category.slug', ['locale'=>'cs','slug'=>'nic']));
        $response->assertStatus(404);
    }

    #[Test]
    public function category_by_id_displays_pages(): void
    {
    $page = $this->mockPage(['category_id'=>7]);
    $cat = new PageCategory();
    $cat->name = ['cs'=>'Kategorie','en'=>'Category'];
    $cat->slug = ['cs'=>'kategorie-7','en'=>'category-7'];
    $page->category = $cat;
        $mock = $this->mock(PageServiceInterface::class);
    $mock->shouldReceive('getPagesByCategory')->once()->andReturn(new EloquentCollection([$page]));
    $mock->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([$page]));
        $response = $this->get(route('frontend.pages.category', ['locale'=>'cs','categoryId'=>7]));
        $response->assertOk()->assertViewIs('frontend.pages.category');
    }

    #[Test]
    public function category_by_id_404_when_empty(): void
    {
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getPagesByCategory')->once()->andReturn(new EloquentCollection([]));
        $response = $this->get(route('frontend.pages.category', ['locale'=>'cs','categoryId'=>99]));
        $response->assertStatus(404);
    }

    #[Test]
    public function search_requires_min_length(): void
    {
        $this->mock(PageServiceInterface::class)
            ->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([]));
        $response = $this->get(route('frontend.pages.search', ['locale'=>'cs','q'=>'ab']));
        $response->assertOk()->assertViewIs('frontend.pages.search')
            ->assertViewHas('pages', function($c){ return $c->count()===0; });
    }

    #[Test]
    public function search_returns_results_when_long_enough(): void
    {
        $page = $this->mockPage();
    $mockService = $this->mock(PageServiceInterface::class);
        $mockService->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([]));
    $mockService->shouldReceive('searchPages')->once()->andReturn(new EloquentCollection([$page]));
        $response = $this->get(route('frontend.pages.search', ['locale'=>'cs','q'=>'testovani']));
        $response->assertOk()->assertViewIs('frontend.pages.search')
            ->assertViewHas('pages', function($c){ return $c->count()===1; });
    }

    #[Test]
    public function sitemap_displays_categories_with_pages(): void
    {
    $mockService = $this->mock(PageServiceInterface::class);
        $mockService->shouldReceive('getCategoriesWithPages')->once()->andReturn(new EloquentCollection([]));
        $mockService->shouldReceive('getPagesForNavigation')->once()->andReturn(new EloquentCollection([]));
        $response = $this->get(route('frontend.pages.sitemap', ['locale'=>'cs']));
        $response->assertOk()->assertViewIs('frontend.pages.sitemap');
    }
}

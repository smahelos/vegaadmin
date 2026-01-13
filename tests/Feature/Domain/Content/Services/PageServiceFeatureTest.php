<?php

namespace Tests\Feature\Domain\Content\Services;

use App\Models\Page;
use App\Models\PageCategory;
use App\Application\Content\Contracts\PageApplicationServiceInterface as PageServiceInterface;
use App\Domain\Content\DTO\PageDTO;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PageServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PageServiceInterface $pageService;

    protected function setUp(): void
    {
        parent::setUp();
    $this->pageService = app(PageServiceInterface::class);
    }

    #[Test]
    public function it_can_get_homepage()
    {
        // Test with no pages
        $this->assertNull($this->pageService->getHomepage());

        // Create a published page
        $page = Page::factory()->published()->create([
            'slug' => ['cs' => 'stranka', 'en' => 'page'],
        ]);
        
        // Should return first published page when no homepage exists
        $homepage = $this->pageService->getHomepage();
        $this->assertNotNull($homepage);
        $this->assertEquals($page->id, $homepage->id);

        // Create a home page
        $homePage = Page::factory()->published()->create([
            'slug' => ['cs' => 'uvod', 'en' => 'home'],
        ]);

        // Should return the home page
        $homepage = $this->pageService->getHomepage();
        $this->assertNotNull($homepage);
        $this->assertEquals($homePage->id, $homepage->id);
        
        // Should respect locale
        $homepage = $this->pageService->getHomepage('en');
        $this->assertNotNull($homepage);
        $this->assertEquals($homePage->id, $homepage->id);
    }

    #[Test]
    public function it_can_get_page_by_slug()
    {
        $page = Page::factory()->published()->create([
            'slug' => ['cs' => 'testovaci-stranka', 'en' => 'test-page'],
        ]);

        $foundPage = $this->pageService->getPageBySlug('testovaci-stranka', 'cs');
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);

        $foundPage = $this->pageService->getPageBySlug('test-page', 'en');
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);

        $foundPage = $this->pageService->getPageBySlug((string)$page->id);
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);

        $this->assertNull($this->pageService->getPageBySlug('neexistujici-stranka'));
    }

    #[Test]
    public function it_can_get_page_by_cross_language_slug()
    {
        $page = Page::factory()->published()->create([
            'slug' => ['cs' => 'sablony', 'en' => 'templates', 'de' => 'vorlagen'],
        ]);

        $foundPage = $this->pageService->getPageBySlug('sablony', 'cs');
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);

        $foundPage = $this->pageService->getPageBySlug('sablony', 'en');
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);

        $foundPage = $this->pageService->getPageBySlug('templates', 'cs');
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);
    }

    #[Test]
    public function it_can_get_pages_for_navigation()
    {
        $rootPage1 = Page::factory()->published()->root()->create();
        $rootPage2 = Page::factory()->published()->root()->create();
        Page::factory()->published()->withParent($rootPage1)->create();
        Page::factory()->published()->withParent($rootPage1)->create();
        Page::factory()->draft()->root()->create();

    $navigationPages = $this->pageService->getPagesForNavigation();
        
    $this->assertInstanceOf(Collection::class, $navigationPages);
    $this->assertCount(2, $navigationPages);
    $this->assertTrue($navigationPages->pluck('id')->contains($rootPage1->id));
    $this->assertTrue($navigationPages->pluck('id')->contains($rootPage2->id));
    $firstPage = $navigationPages->firstWhere('id', $rootPage1->id);
        $this->assertCount(2, $firstPage->children);
    }

    #[Test]
    public function it_can_get_pages_by_category()
    {
        $category = PageCategory::factory()->create();
        $page1 = Page::factory()->published()->inCategory($category)->create();
        $page2 = Page::factory()->published()->inCategory($category)->create();
        $otherCategory = PageCategory::factory()->create();
        Page::factory()->published()->inCategory($otherCategory)->create();
        
    $categoryPages = $this->pageService->getPagesByCategory($category->id);
        
    $this->assertInstanceOf(Collection::class, $categoryPages);
    $this->assertCount(2, $categoryPages);
    $this->assertTrue($categoryPages->pluck('id')->contains($page1->id));
    $this->assertTrue($categoryPages->pluck('id')->contains($page2->id));
    }

    #[Test]
    public function it_can_search_pages()
    {
        $page1 = Page::factory()->published()->create([
            'name' => ['cs' => 'Testovací stránka', 'en' => 'Test page'],
            'content' => ['cs' => 'Toto je obsah testovací stránky', 'en' => 'This is test page content'],
        ]);
        $page2 = Page::factory()->published()->create([
            'name' => ['cs' => 'Jiná stránka', 'en' => 'Another page'],
            'content' => ['cs' => 'Toto je jiný obsah', 'en' => 'This is different content'],
        ]);
        
    $searchResults = $this->pageService->searchPages('testovací', 'cs');
    $this->assertCount(1, $searchResults);
    $this->assertTrue($searchResults->pluck('id')->contains($page1->id));
    $this->assertFalse($searchResults->pluck('id')->contains($page2->id));
        
    $searchResults = $this->pageService->searchPages('test', 'en');
    $this->assertCount(1, $searchResults);
    $this->assertTrue($searchResults->pluck('id')->contains($page1->id));
        
        $searchResults = $this->pageService->searchPages('obsah', 'cs');
        $this->assertCount(2, $searchResults);
    }

    #[Test]
    public function it_can_get_breadcrumbs()
    {
        $rootPage = Page::factory()->published()->root()->create([
            'name' => ['cs' => 'Kořenová stránka', 'en' => 'Root page'],
            'slug' => ['cs' => 'korenova', 'en' => 'root'],
        ]);
        $middlePage = Page::factory()->published()->withParent($rootPage)->create([
            'name' => ['cs' => 'Střední stránka', 'en' => 'Middle page'],
            'slug' => ['cs' => 'stredni', 'en' => 'middle'],
        ]);
        $leafPage = Page::factory()->published()->withParent($middlePage)->create([
            'name' => ['cs' => 'Koncová stránka', 'en' => 'Leaf page'],
            'slug' => ['cs' => 'koncova', 'en' => 'leaf'],
        ]);
    $breadcrumbs = $this->pageService->getBreadcrumbs($this->pageService->getPageBySlug((string)$leafPage->id));
        $this->assertIsArray($breadcrumbs);
        $this->assertGreaterThanOrEqual(2, count($breadcrumbs));
        $this->assertEquals($rootPage->getName(), $breadcrumbs[0]['name']);
        $this->assertEquals($middlePage->getName(), $breadcrumbs[1]['name']);
    }

    #[Test]
    public function it_can_get_related_pages()
    {
        $category = PageCategory::factory()->create();
        $mainPage = Page::factory()->published()->inCategory($category)->create();
        $relatedPage1 = Page::factory()->published()->inCategory($category)->create();
        $relatedPage2 = Page::factory()->published()->inCategory($category)->create();
        $relatedPage3 = Page::factory()->published()->inCategory($category)->create();
        $otherCategory = PageCategory::factory()->create();
        Page::factory()->published()->inCategory($otherCategory)->create();
    $relatedPages = $this->pageService->getRelatedPages($this->pageService->getPageBySlug((string)$mainPage->id));
    $this->assertInstanceOf(Collection::class, $relatedPages);
    $this->assertCount(3, $relatedPages);
    $this->assertFalse($relatedPages->pluck('id')->contains($mainPage->id));
    $limitedRelatedPages = $this->pageService->getRelatedPages($this->pageService->getPageBySlug((string)$mainPage->id), 2);
        $this->assertCount(2, $limitedRelatedPages);
    }

    #[Test]
    public function it_returns_empty_collection_for_page_without_category()
    {
    $page = Page::factory()->published()->create(['category_id' => null]);
    $relatedPages = $this->pageService->getRelatedPages($this->pageService->getPageBySlug((string)$page->id));
        $this->assertInstanceOf(Collection::class, $relatedPages);
        $this->assertTrue($relatedPages->isEmpty());
    }
}

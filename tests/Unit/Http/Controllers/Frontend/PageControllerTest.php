<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\PageController;
use App\Application\Content\Contracts\PageApplicationServiceInterface;
use App\Models\Page;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    private PageApplicationServiceInterface $pageAppServiceStub;

    protected function setUp(): void
    {
        parent::setUp();
        // Simple stub implementing only methods used
        $this->pageAppServiceStub = new class implements PageApplicationServiceInterface {
            public function getHomepage(?string $locale = null): ?Page { return null; }
            public function getPageBySlug(string $slug, ?string $locale = null): ?Page { return null; }
            public function getPagesForNavigation(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPagesByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPageContentById(int $pageId): ?Page { return null; }
            public function getCategoriesWithPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function searchPages(string $keyword, ?string $locale = null): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getBreadcrumbs(Page $page): array { return []; }
            public function getRelatedPages(Page $page, int $limit = 5): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getAllPublishedPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getCategoryBySlug(string $slug, ?string $locale = null): ?\App\Models\PageCategory { return null; }
        };
    }
    #[Test]
    public function constructor_has_one_parameter(): void
    {
        $reflection = new \ReflectionClass(PageController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $param = $constructor->getParameters()[0];
        $this->assertEquals(PageApplicationServiceInterface::class, $param->getType()->getName());
    }

    #[Test]
    public function homepage_returns_empty_view_when_no_page(): void
    {
        $service = new class implements PageApplicationServiceInterface {
            public function getHomepage(?string $locale = null): ?Page { return null; }
            public function getPageBySlug(string $slug, ?string $locale = null): ?Page { return null; }
            public function getPagesForNavigation(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPagesByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPageContentById(int $pageId): ?Page { return null; }
            public function getCategoriesWithPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function searchPages(string $keyword, ?string $locale = null): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getBreadcrumbs(Page $page): array { return []; }
            public function getRelatedPages(Page $page, int $limit = 5): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getAllPublishedPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getCategoryBySlug(string $slug, ?string $locale = null): ?\App\Models\PageCategory { return null; }
        };
        $controller = new PageController($service);
        $view = $controller->homepage();
        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('frontend.pages.homepage-empty', $view->name());
    }

    #[Test]
    public function index_returns_expected_view_and_variables(): void
    {
        $controller = new PageController($this->pageAppServiceStub);
        $view = $controller->index();
        $this->assertEquals('frontend.pages.index', $view->name());
        $data = $view->getData();
        $this->assertArrayHasKey('pages', $data);
        $this->assertArrayHasKey('categoriesWithPages', $data);
        $this->assertArrayHasKey('navigationPages', $data);
    }

    #[Test]
    public function show_redirects_when_slug_mismatch(): void
    {
        $page = new Page();
        $page->slug = ['cs' => 'spravny', 'en' => 'correct'];
        $service = new class($page) implements PageApplicationServiceInterface {
            public function __construct(private Page $page) {}
            public function getHomepage(?string $locale = null): ?Page { return null; }
            public function getPageBySlug(string $slug, ?string $locale = null): ?Page { return $this->page; }
            public function getPagesForNavigation(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPagesByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPageContentById(int $pageId): ?Page { return null; }
            public function getCategoriesWithPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function searchPages(string $keyword, ?string $locale = null): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getBreadcrumbs(Page $page): array { return []; }
            public function getRelatedPages(Page $page, int $limit = 5): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getAllPublishedPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getCategoryBySlug(string $slug, ?string $locale = null): ?\App\Models\PageCategory { return null; }
        };
        $controller = new PageController($service);
        // Simulate mismatch: request slug 'neco' but localized is 'spravny'
        $response = $controller->show('cs', 'neco');
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringEndsWith('/cs/pages/spravny', $response->getTargetUrl());
    }

    #[Test]
    public function show_returns_view_when_slug_matches(): void
    {
        $page = new Page();
        $page->slug = ['cs' => 'shoda'];
        $service = new class($page) implements PageApplicationServiceInterface {
            public function __construct(private Page $page) {}
            public function getHomepage(?string $locale = null): ?Page { return null; }
            public function getPageBySlug(string $slug, ?string $locale = null): ?Page { return $this->page; }
            public function getPagesForNavigation(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPagesByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPageContentById(int $pageId): ?Page { return null; }
            public function getCategoriesWithPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function searchPages(string $keyword, ?string $locale = null): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getBreadcrumbs(Page $page): array { return [['name' => 'root', 'slug' => 'root']]; }
            public function getRelatedPages(Page $page, int $limit = 5): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getAllPublishedPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getCategoryBySlug(string $slug, ?string $locale = null): ?\App\Models\PageCategory { return null; }
        };
        $controller = new PageController($service);
        $viewOrRedirect = $controller->show('cs', 'shoda');
        $this->assertInstanceOf(View::class, $viewOrRedirect);
        $this->assertEquals('frontend.pages.show', $viewOrRedirect->name());
        $data = $viewOrRedirect->getData();
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('breadcrumbs', $data);
        $this->assertArrayHasKey('relatedPages', $data);
        $this->assertArrayHasKey('navigationPages', $data);
    }

    #[Test]
    public function search_returns_empty_collection_when_keyword_too_short(): void
    {
        $controller = new PageController($this->pageAppServiceStub);
        $request = new \Illuminate\Http\Request(['q' => 'ab']);
        $view = $controller->search($request, 'cs');
        $this->assertEquals('frontend.pages.search', $view->name());
        $this->assertCount(0, $view->getData()['pages']);
    }

    #[Test]
    public function search_returns_results_when_keyword_long_enough(): void
    {
        $page = new Page();
        $page->slug = ['cs' => 'test'];
        $service = new class($page) implements PageApplicationServiceInterface {
            public function __construct(private Page $p) {}
            public function getHomepage(?string $locale = null): ?Page { return null; }
            public function getPageBySlug(string $slug, ?string $locale = null): ?Page { return null; }
            public function getPagesForNavigation(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPagesByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getPageContentById(int $pageId): ?Page { return null; }
            public function getCategoriesWithPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function searchPages(string $keyword, ?string $locale = null): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection([$this->p]); }
            public function getBreadcrumbs(Page $page): array { return []; }
            public function getRelatedPages(Page $page, int $limit = 5): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getAllPublishedPages(): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
            public function getCategoryBySlug(string $slug, ?string $locale = null): ?\App\Models\PageCategory { return null; }
        };
        $controller = new PageController($service);
        $request = new \Illuminate\Http\Request(['q' => 'abcd']);
        $view = $controller->search($request, 'cs');
        $this->assertEquals('frontend.pages.search', $view->name());
        $this->assertCount(1, $view->getData()['pages']);
    }
}

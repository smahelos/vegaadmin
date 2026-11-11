<?php

namespace Tests\Feature\Domain\Content\Services;

use App\Application\Content\Contracts\PageApplicationServiceInterface as PageService;
use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceCategoriesFeatureTest extends TestCase
{
    private PageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PageService::class);
    }

    #[Test]
    public function returns_only_categories_with_published_pages(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $catWithPages = $pageCategoryFactory->create();
        $catEmpty = $pageCategoryFactory->create();
        $catWithDraftOnly = $pageCategoryFactory->create();

        // Published pages
        $pageFactory = Page::factory();
        $publishedFactory = $pageFactory->published();
        $categorizedFactory = $publishedFactory->inCategory($catWithPages);
        $categorizedFactory->create();
        $categorizedFactory->create();

        // Draft page (should not count)
        $draftFactory = $pageFactory->draft();
        $draftFactory = $draftFactory->inCategory($catWithDraftOnly);
        $draftFactory->create();

        $categories = $this->service->getCategoriesWithPages();

        $this->assertInstanceOf(Collection::class, $categories);
        $this->assertTrue(
            $categories->contains('id', $catWithPages->id),
        );
        $this->assertFalse(
            $categories->contains('id', $catEmpty->id),
        );
        $this->assertFalse(
            $categories->contains('id', $catWithDraftOnly->id),
        );

        // Eager loaded pages are only published and ordered
        $returned = $categories->firstWhere('id', $catWithPages->id);
        $this->assertInstanceOf(PageCategory::class, $returned);
        $this->assertCount(2, $returned->pages);
        $this->assertTrue(
            $returned->pages->every(fn ($p) => $p->published),
        );
    }
}

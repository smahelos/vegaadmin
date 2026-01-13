<?php

namespace Tests\Feature\Domain\Content\Services;

use App\Application\Content\Contracts\PageApplicationServiceInterface as PageService;
use App\Models\Page;
use App\Models\PageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceCategoriesFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PageService $service;

    protected function setUp(): void
    {
        parent::setUp();
    $this->service = app(PageService::class);
    }

    #[Test]
    public function returns_only_categories_with_published_pages(): void
    {
        $catWithPages = PageCategory::factory()->create();
        $catEmpty = PageCategory::factory()->create();
        $catWithDraftOnly = PageCategory::factory()->create();

        // Published pages
        Page::factory()->published()->inCategory($catWithPages)->create();
        Page::factory()->published()->inCategory($catWithPages)->create();

        // Draft page (should not count)
        Page::factory()->draft()->inCategory($catWithDraftOnly)->create();

        $categories = $this->service->getCategoriesWithPages();

        $this->assertInstanceOf(Collection::class, $categories);
        $this->assertTrue($categories->contains('id', $catWithPages->id));
        $this->assertFalse($categories->contains('id', $catEmpty->id));
        $this->assertFalse($categories->contains('id', $catWithDraftOnly->id));

        // Eager loaded pages are only published and ordered
        $returned = $categories->firstWhere('id', $catWithPages->id);
        $this->assertCount(2, $returned->pages);
        $this->assertTrue($returned->pages->every(fn($p) => $p->published));
    }
}

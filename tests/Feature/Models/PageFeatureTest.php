<?php

namespace Tests\Feature\Models;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature test for Page model.
 * Tests model relationships, database operations, and integration behavior.
 */
class PageFeatureTest extends TestCase
{
    #[Test]
    public function page_can_be_created_with_valid_data(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        $pageData = [
            'name' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Test content',
            'description' => 'Test description',
            'category_id' => $category->id,
            'published' => true,
            'published_by' => $user->id,
            'sort_order' => 1,
        ];

        $page = Page::create($pageData);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('Test Page', $page->name);
        $this->assertEquals('test-page', $page->slug);
        $this->assertTrue($page->published);
        $this->assertEquals($category->id, $page->category_id);
        $this->assertEquals($user->id, $page->published_by);
    }

    #[Test]
    public function page_belongs_to_category(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create(['name' => 'Test Category']);
        $pageFactory = Page::factory();
        $page = $pageFactory->create(['category_id' => $category->id]);

        $this->assertInstanceOf(PageCategory::class, $page->category);
        $this->assertEquals('Test Category', $page->category->name);
        $this->assertEquals($category->id, $page->category->id);
    }

    #[Test]
    public function page_can_have_parent(): void
    {
        $pageFactory = Page::factory();
        $parentPage = $pageFactory->create(['name' => 'Parent Page']);
        $childPage = $pageFactory->create(
            [
                'name' => 'Child Page',
                'parent_id' => $parentPage->id,
            ],
        );

        $this->assertInstanceOf(Page::class, $childPage->parent);
        $this->assertEquals('Parent Page', $childPage->parent->name);
        $this->assertEquals($parentPage->id, $childPage->parent->id);
    }

    #[Test]
    public function page_can_have_children(): void
    {
        $pageFactory = Page::factory();
        $parentPage = $pageFactory->create(['name' => 'Parent Page']);
        $childPage1 = $pageFactory->create(
            [
                'name' => 'Child Page 1',
                'parent_id' => $parentPage->id,
            ],
        );
        $childPage2 = $pageFactory->create(
            [
                'name' => 'Child Page 2',
                'parent_id' => $parentPage->id,
            ],
        );

        $children = $parentPage->children;

        $this->assertCount(2, $children);
        $this->assertTrue(
            $children->contains('name', 'Child Page 1'),
        );
        $this->assertTrue(
            $children->contains('name', 'Child Page 2'),
        );
    }

    #[Test]
    public function page_belongs_to_published_by_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create(['name' => 'Test User']);
        $pageFactory = Page::factory();
        $page = $pageFactory->create(['published_by' => $user->id]);

        $this->assertInstanceOf(User::class, $page->publishedBy);
        $this->assertEquals('Test User', $page->publishedBy->name);
        $this->assertEquals($user->id, $page->publishedBy->id);
    }

    #[Test]
    public function page_images_are_cast_to_array(): void
    {
        // Page má custom mutator pro images, který používá HasFileUploads trait
        // Pro testování castů použijeme raw DB přístup

        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        // Test 1: null hodnota
        $pageNull = Page::create(
            [
                'name' => 'Test Page Null',
                'slug' => 'test-page-null',
                'category_id' => $category->id,
                'published' => true,
                'published_by' => $user->id,
            ],
        );
        // Nastavíme images přímo do DB (obejdeme mutator)
        \DB::table('pages')->where('id', $pageNull->id)->update(['images' => null]);
        $this->assertNull($pageNull->fresh()->images);

        // Test 2: Prázdný array
        $pageEmpty = Page::create(
            [
                'name' => 'Test Page Empty',
                'slug' => 'test-page-empty',
                'category_id' => $category->id,
                'published' => true,
                'published_by' => $user->id,
            ],
        );
        \DB::table('pages')->where('id', $pageEmpty->id)->update(['images' => '[]']);
        $this->assertIsArray($pageEmpty->fresh()->images);
        $this->assertEquals([], $pageEmpty->fresh()->images);

        // Test 3: Array s daty
        $pageWithData = Page::create(
            [
                'name' => 'Test Page Data',
                'slug' => 'test-page-data',
                'category_id' => $category->id,
                'published' => true,
                'published_by' => $user->id,
            ],
        );
        \DB::table('pages')->where('id', $pageWithData->id)->update(
            [
                'images' => json_encode(['image1.jpg', 'image2.jpg']),
            ],
        );
        $pageData = $pageWithData->fresh();
        $this->assertIsArray($pageData->images);
        $this->assertCount(2, $pageData->images);
        $this->assertContains('image1.jpg', $pageData->images);
        $this->assertContains('image2.jpg', $pageData->images);
    }

    #[Test]
    public function page_publishing_dates_are_cast_to_datetime(): void
    {
        $pageFactory = Page::factory();
        $page = $pageFactory->create(
            [
                'publishing_start' => '2025-01-01 10:00:00',
                'publishing_end' => '2025-12-31 23:59:59',
            ],
        );

        $this->assertInstanceOf(Carbon::class, $page->publishing_start);
        $this->assertInstanceOf(Carbon::class, $page->publishing_end);
        $this->assertEquals(
            '2025-01-01 10:00:00',
            $page->publishing_start->format('Y-m-d H:i:s'),
        );
        $this->assertEquals(
            '2025-12-31 23:59:59',
            $page->publishing_end->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function page_published_is_cast_to_boolean(): void
    {
        $pageFactory = Page::factory();
        $publishedPage = $pageFactory->create(['published' => 1]);
        $unpublishedPage = $pageFactory->create(['published' => 0]);

        $this->assertTrue($publishedPage->published);
        $this->assertFalse($unpublishedPage->published);
        $this->assertIsBool($publishedPage->published);
        $this->assertIsBool($unpublishedPage->published);
    }

    #[Test]
    public function page_can_be_updated(): void
    {
        $pageFactory = Page::factory();
        $page = $pageFactory->create(['name' => 'Original Name']);

        $page->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $page->fresh()->name);
    }

    #[Test]
    public function page_can_be_deleted(): void
    {
        $pageFactory = Page::factory();
        $page = $pageFactory->create();
        $pageId = $page->id;

        $page->delete();

        $this->assertDatabaseMissing('pages', ['id' => $pageId]);
    }

    #[Test]
    public function page_category_relationship_returns_belongs_to(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();
        $pageFactory = Page::factory();
        $page = $pageFactory->create(['category_id' => $category->id]);

        $this->assertInstanceOf(BelongsTo::class, $page->category());
    }

    #[Test]
    public function page_parent_relationship_returns_belongs_to(): void
    {
        $pageFactory = Page::factory();
        $parentPage = $pageFactory->create();
        $childPage = $pageFactory->create(['parent_id' => $parentPage->id]);

        $this->assertInstanceOf(BelongsTo::class, $childPage->parent());
    }

    #[Test]
    public function page_children_relationship_returns_has_many(): void
    {
        $pageFactory = Page::factory();
        $page = $pageFactory->create();

        $this->assertInstanceOf(HasMany::class, $page->children());
    }

    #[Test]
    public function page_published_by_relationship_returns_belongs_to(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $pageFactory = Page::factory();
        $page = $pageFactory->create(['published_by' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $page->publishedBy());
    }

    #[Test]
    public function page_scope_published_filters_published_pages(): void
    {
        // The scope reads the whole table; start without the seeded content pages
        $pageQuery = Page::query();
        $pageQuery->delete();

        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();

        // Published page with no time restrictions
        $pageFactory = Page::factory();
        $publishedPage = $pageFactory->create(
            [
                'published' => true,
                'category_id' => $category->id,
                'publishing_start' => null,
                'publishing_end' => null,
            ],
        );

        // Unpublished page
        $unpublishedPage = $pageFactory->create([
            'published' => false,
            'category_id' => $category->id,
        ]);

        // Published page with future start date
        $now = CarbonImmutable::now();
        $futurePublishedPage = $pageFactory->create(
            [
                'published' => true,
                'category_id' => $category->id,
                'publishing_start' => $now->addDay(),
                'publishing_end' => null,
            ],
        );

        // Published page with past end date
        $expiredPublishedPage = $pageFactory->create(
            [
                'published' => true,
                'category_id' => $category->id,
                'publishing_start' => $now->subWeek(),
                'publishing_end' => now()->subDay(),
            ],
        );

        $publishedPages = Page::published()->get();

        $this->assertCount(1, $publishedPages);
        $this->assertTrue(
            $publishedPages->contains('id', $publishedPage->id),
        );
        $this->assertFalse(
            $publishedPages->contains('id', $unpublishedPage->id),
        );
        $this->assertFalse(
            $publishedPages->contains('id', $futurePublishedPage->id),
        );
        $this->assertFalse(
            $publishedPages->contains('id', $expiredPublishedPage->id),
        );
    }

    #[Test]
    public function page_scope_by_category_filters_by_category(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category1 = $pageCategoryFactory->create();
        $category2 = $pageCategoryFactory->create();

        $pageFactory = Page::factory();
        $pageInCategory1 = $pageFactory->create(['category_id' => $category1->id]);
        $pageInCategory2 = $pageFactory->create(['category_id' => $category2->id]);

        $pagesInCategory1 = Page::byCategory($category1->id)->get();

        $this->assertCount(1, $pagesInCategory1);
        $this->assertTrue(
            $pagesInCategory1->contains('id', $pageInCategory1->id),
        );
        $this->assertFalse(
            $pagesInCategory1->contains('id', $pageInCategory2->id),
        );
    }

    #[Test]
    public function page_scope_root_pages_filters_pages_without_parent(): void
    {
        $pageFactory = Page::factory();
        $parentPage = $pageFactory->create();
        $rootPage = $pageFactory->create(['parent_id' => null]);
        $childPage = $pageFactory->create(['parent_id' => $parentPage->id]);

        $rootPages = Page::rootPages()->get();

        $this->assertTrue(
            $rootPages->contains('id', $parentPage->id),
        );
        $this->assertTrue(
            $rootPages->contains('id', $rootPage->id),
        );
        $this->assertFalse(
            $rootPages->contains('id', $childPage->id),
        );
    }

    #[Test]
    public function page_is_currently_published_attribute_works_correctly(): void
    {
        // Published with no time restrictions
        $pageFactory = Page::factory();
        $alwaysPublished = $pageFactory->create(
            [
                'published' => true,
                'publishing_start' => null,
                'publishing_end' => null,
            ],
        );

        // Unpublished
        $unpublished = $pageFactory->create([
            'published' => false,
        ]);

        // Published but future start date
        $now = CarbonImmutable::now();
        $futurePublished = $pageFactory->create(
            [
                'published' => true,
                'publishing_start' => $now->addDay(),
            ],
        );

        // Published but past end date
        $expiredPublished = $pageFactory->create(
            [
                'published' => true,
                'publishing_start' => $now->subWeek(),
                'publishing_end' => now()->subDay(),
            ],
        );

        // Currently published (within time window)
        $currentlyPublished = $pageFactory->create(
            [
                'published' => true,
                'publishing_start' => $now->subDay(),
                'publishing_end' => $now->addDay(),
            ],
        );

        $this->assertTrue($alwaysPublished->is_currently_published);
        $this->assertFalse($unpublished->is_currently_published);
        $this->assertFalse($futurePublished->is_currently_published);
        $this->assertFalse($expiredPublished->is_currently_published);
        $this->assertTrue($currentlyPublished->is_currently_published);
    }

    #[Test]
    public function page_breadcrumbs_attribute_returns_correct_hierarchy(): void
    {
        $pageFactory = Page::factory();
        $grandparent = $pageFactory->create(['name' => 'Grandparent', 'slug' => 'grandparent']);
        $parent = $pageFactory->create(
            [
                'name' => 'Parent',
                'slug' => 'parent',
                'parent_id' => $grandparent->id,
            ],
        );
        $child = $pageFactory->create(
            [
                'name' => 'Child',
                'slug' => 'child',
                'parent_id' => $parent->id,
            ],
        );

        $breadcrumbs = $child->breadcrumbs;

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Grandparent', $breadcrumbs[0]['name']);
        $this->assertEquals('grandparent', $breadcrumbs[0]['slug']);
        $this->assertEquals($grandparent->id, $breadcrumbs[0]['id']);

        $this->assertEquals('Parent', $breadcrumbs[1]['name']);
        $this->assertEquals('parent', $breadcrumbs[1]['slug']);
        $this->assertEquals($parent->id, $breadcrumbs[1]['id']);

        $this->assertEquals('Child', $breadcrumbs[2]['name']);
        $this->assertEquals('child', $breadcrumbs[2]['slug']);
        $this->assertEquals($child->id, $breadcrumbs[2]['id']);
    }

    #[Test]
    public function page_auto_sets_sort_order_on_creation(): void
    {
        // Vytvoříme kategorii
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        // Přihlásíme uživatele pro testování boot metody
        $this->actingAs($user, 'web');

        // První stránka by měla mít sort_order 1
        $page1 = Page::create(
            [
                'name' => 'First Page',
                'category_id' => $category->id,
                'published' => true,
            ],
        );

        // Druhá stránka by měla mít sort_order 2
        $page2 = Page::create(
            [
                'name' => 'Second Page',
                'category_id' => $category->id,
                'published' => true,
            ],
        );

        $this->assertEquals(1, $page1->sort_order);
        $this->assertEquals(2, $page2->sort_order);
    }

    #[Test]
    public function page_auto_sets_published_by_on_creation_when_user_authenticated(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();
        $userFactory = User::factory();
        $user = $userFactory->create();

        // Přihlásíme uživatele pro testování boot metody
        $this->actingAs($user, 'web');

        $page = Page::create(
            [
                'name' => 'Test Page',
                'category_id' => $category->id,
                'published' => true,
            ],
        );

        $this->assertEquals($user->id, $page->published_by);
    }

    #[Test]
    public function page_does_not_auto_set_published_by_when_user_not_authenticated(): void
    {
        $pageCategoryFactory = PageCategory::factory();
        $category = $pageCategoryFactory->create();

        $page = Page::create(
            [
                'name' => 'Test Page',
                'category_id' => $category->id,
                'published' => true,
            ],
        );

        $this->assertNull($page->published_by);
    }
}

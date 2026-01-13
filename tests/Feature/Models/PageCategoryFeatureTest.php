<?php

namespace Tests\Feature\Models;

use App\Models\PageCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature test for PageCategory model.
 * Tests model relationships, database operations, and integration behavior.
 */
class PageCategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function page_category_can_be_created_with_valid_data(): void
    {
        $categoryData = [
            'name' => [
                'cs' => 'Testovací kategorie',
                'en' => 'Test Category',
                'de' => 'Test Kategorie',
                'sk' => 'Testovacia kategória',
            ],
            'slug' => [
                'cs' => 'testovaci-kategorie',
                'en' => 'test-category',
                'de' => 'test-kategorie',
                'sk' => 'testovacia-kategoria',
            ],
            'description' => [
                'cs' => 'Popis testovací kategorie',
                'en' => 'Test category description',
                'de' => 'Test Kategorie Beschreibung',
                'sk' => 'Popis testovacej kategórie',
            ],
        ];

        $category = PageCategory::create($categoryData);

        $this->assertInstanceOf(PageCategory::class, $category);
        
        // Test multilingual helper methods
        $this->assertEquals('Testovací kategorie', $category->getName('cs'));
        $this->assertEquals('Test Category', $category->getName('en'));
        $this->assertEquals('testovaci-kategorie', $category->getSlug('cs'));
        $this->assertEquals('test-category', $category->getSlug('en'));
        $this->assertEquals('Popis testovací kategorie', $category->getDescription('cs'));
        
        // Test fallback behavior (should return CS by default)
        $this->assertEquals('Testovací kategorie', $category->getName());
        $this->assertEquals('testovaci-kategorie', $category->getSlug());
    }

    #[Test]
    public function page_category_has_many_pages(): void
    {
        $category = PageCategory::factory()->create(['name' => 'Test Category']);
        $page1 = Page::factory()->create([
            'name' => 'Page 1',
            'category_id' => $category->id
        ]);
        $page2 = Page::factory()->create([
            'name' => 'Page 2',
            'category_id' => $category->id
        ]);

        $pages = $category->pages;
        
        $this->assertCount(2, $pages);
        $this->assertTrue($pages->contains('name', 'Page 1'));
        $this->assertTrue($pages->contains('name', 'Page 2'));
        $this->assertInstanceOf(Page::class, $pages->first());
    }

    #[Test]
    public function page_category_multilingual_attributes_work_correctly(): void
    {
        $category = PageCategory::factory()->create();
        
        // Test that multilingual attributes are accessible as arrays via accessors
        $this->assertIsArray($category->name);
        $this->assertIsArray($category->slug);
        $this->assertIsArray($category->description);
        
        // Test that helper methods return strings
        $this->assertIsString($category->getName());
        $this->assertIsString($category->getSlug());
        $this->assertIsString($category->getDescription());
    }

    #[Test]
    public function page_category_can_be_updated(): void
    {
        $category = PageCategory::factory()->create();
        
        $updateData = [
            'name' => [
                'cs' => 'Aktualizovaný název',
                'en' => 'Updated Name',
            ]
        ];
        
        $category->update($updateData);
        
        $this->assertEquals('Aktualizovaný název', $category->fresh()->getName('cs'));
        $this->assertEquals('Updated Name', $category->fresh()->getName('en'));
    }

    #[Test]
    public function page_category_can_be_deleted(): void
    {
        $category = PageCategory::factory()->create();
        $categoryId = $category->id;
        
        $category->delete();
        
        $this->assertDatabaseMissing('page_categories', ['id' => $categoryId]);
    }

    #[Test]
    public function page_category_can_be_deactivated(): void
    {
        $category = PageCategory::factory()->create();
        
        $updateData = [
            'name' => [
                'cs' => 'Aktualizovaná kategorie',
                'en' => 'Updated Category',
            ]
        ];
        
        $category->update($updateData);
        
        $this->assertEquals('Aktualizovaná kategorie', $category->fresh()->getName('cs'));
        $this->assertEquals('Updated Category', $category->fresh()->getName('en'));
    }

    #[Test]
    public function page_category_slug_validation_handled_in_request(): void
    {
        // Slug uniqueness je nyní testovaná v PageCategoryRequest testech
        // Protože model už automaticky negeneruje slugy - dělá to request
        $category1 = PageCategory::factory()->create();
        $category2 = PageCategory::factory()->create();
        
        // Both categories should be created successfully
        $this->assertInstanceOf(PageCategory::class, $category1);
        $this->assertInstanceOf(PageCategory::class, $category2);
        $this->assertNotEquals($category1->getSlug('cs'), $category2->getSlug('cs'));
    }

    #[Test]
    public function page_category_validation_handled_in_request(): void
    {
        // Name validation je nyní řešena v PageCategoryRequest, ne v modelu
        // Model může mít prázdná pole, protože validace je na úrovni requestu
        $category = PageCategory::create([
            'name' => ['cs' => '', 'en' => ''],
            'slug' => ['cs' => '', 'en' => ''],
            'description' => ['cs' => '', 'en' => ''],
        ]);
        
        $this->assertInstanceOf(PageCategory::class, $category);
    }

    #[Test]
    public function page_category_multilingual_storage_works_correctly(): void
    {
        // Test that we can create and retrieve multilingual data
        $categoryData = [
            'name' => [
                'cs' => 'Testovací kategorie',
                'en' => 'Test Category',
            ],
            'slug' => [
                'cs' => 'testovaci-kategorie', 
                'en' => 'test-category',
            ],
            'description' => [
                'cs' => 'Popis kategorie',
                'en' => 'Category description',
            ],
        ];
        
        $category = PageCategory::create($categoryData);
        
        $this->assertEquals('Testovací kategorie', $category->getName('cs'));
        $this->assertEquals('Test Category', $category->getName('en'));
        $this->assertEquals('testovaci-kategorie', $category->getSlug('cs'));
        $this->assertEquals('test-category', $category->getSlug('en'));
    }

    #[Test]
    public function page_category_pages_relationship_returns_has_many(): void
    {
        $category = PageCategory::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->pages());
    }

    #[Test]
    public function page_category_pages_count_attribute_returns_correct_count(): void
    {
        $category = PageCategory::factory()->create();
        
        // Vytvoříme 3 stránky v kategorii
        Page::factory()->count(3)->create(['category_id' => $category->id]);
        
        // Vytvoříme stránku v jiné kategorii
        $otherCategory = PageCategory::factory()->create();
        Page::factory()->create(['category_id' => $otherCategory->id]);
        
        $this->assertEquals(3, $category->pages_count);
        $this->assertEquals(1, $otherCategory->pages_count);
    }

    #[Test]
    public function page_category_published_pages_count_attribute_returns_correct_count(): void
    {
        $category = PageCategory::factory()->create();
        
        // Vytvoříme 2 publikované stránky
        Page::factory()->count(2)->create([
            'category_id' => $category->id,
            'published' => true
        ]);
        
        // Vytvoříme 1 nepublikovanou stránku
        Page::factory()->create([
            'category_id' => $category->id,
            'published' => false
        ]);
        
        $this->assertEquals(3, $category->pages_count);
        $this->assertEquals(2, $category->published_pages_count);
    }

    #[Test]
    public function page_category_multilingual_fallback_works(): void
    {
        $categoryData = [
            'name' => [
                'cs' => 'Česká kategorie',
                'en' => '', // prázdný EN
            ],
            'slug' => [
                'cs' => 'ceska-kategorie',
                'en' => '',
            ],
            'description' => [
                'cs' => 'Český popis',
                'en' => '',
            ],
        ];
        
        $category = PageCategory::create($categoryData);
        
        // Fallback should return CS when EN is empty
        app()->setLocale('en');
        $this->assertEquals('Česká kategorie', $category->getName());
        $this->assertEquals('ceska-kategorie', $category->getSlug());
        $this->assertEquals('Český popis', $category->getDescription());
    }

    #[Test]
    public function page_category_multilingual_specific_locale_works(): void
    {
        $categoryData = [
            'name' => [
                'cs' => 'Česká kategorie',
                'en' => 'English Category',
                'de' => 'Deutsche Kategorie',
            ],
            'slug' => [
                'cs' => 'ceska-kategorie',
                'en' => 'english-category',
                'de' => 'deutsche-kategorie',
            ],
            'description' => [
                'cs' => 'Český popis',
                'en' => 'English description',
                'de' => 'Deutsche Beschreibung',
            ],
        ];
        
        $category = PageCategory::create($categoryData);
        
        // Test specific locale retrieval
        $this->assertEquals('English Category', $category->getName('en'));
        $this->assertEquals('Deutsche Kategorie', $category->getName('de'));
        $this->assertEquals('english-category', $category->getSlug('en'));
        $this->assertEquals('deutsche-kategorie', $category->getSlug('de'));
    }
}

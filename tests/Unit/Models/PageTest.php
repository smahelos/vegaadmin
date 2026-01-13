<?php

namespace Tests\Unit\Models;

use App\Models\Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Page model.
 * Tests model structure without Laravel framework dependencies.
 */
class PageTest extends TestCase
{
    #[Test]
    public function page_class_exists(): void
    {
        $this->assertTrue(class_exists(Page::class));
    }

    #[Test]
    public function page_extends_eloquent_model(): void
    {
        $this->assertTrue(is_subclass_of(Page::class, \Illuminate\Database\Eloquent\Model::class));
    }

    #[Test]
    public function page_uses_expected_traits(): void
    {
        $traits = class_uses_recursive(Page::class);
        
        $this->assertArrayHasKey(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
        // Removed Sluggable trait from multilingual implementation
        $this->assertArrayHasKey(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
    $this->assertArrayHasKey(\App\Infrastructure\Shared\File\Traits\HasFileUploads::class, $traits);
    }

    #[Test]
    public function page_has_expected_methods(): void
    {
        $this->assertTrue(method_exists(Page::class, 'category'));
        $this->assertTrue(method_exists(Page::class, 'parent'));
        $this->assertTrue(method_exists(Page::class, 'children'));
        $this->assertTrue(method_exists(Page::class, 'publishedBy'));
        // Removed sluggable method from multilingual implementation
        $this->assertTrue(method_exists(Page::class, 'scopePublished'));
        $this->assertTrue(method_exists(Page::class, 'scopeByCategory'));
        $this->assertTrue(method_exists(Page::class, 'scopeRootPages'));
        // Add multilingual helper methods
        $this->assertTrue(method_exists(Page::class, 'getName'));
        $this->assertTrue(method_exists(Page::class, 'getSlug'));
        $this->assertTrue(method_exists(Page::class, 'getDescription'));
        $this->assertTrue(method_exists(Page::class, 'getContent'));
        $this->assertTrue(method_exists(Page::class, 'getMetaTitle'));
    }

    #[Test]
    public function page_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'name',
            'slug',
            'description',
            'content',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'category_id',
            'parent_id',
            'sort_order',
            'published',
            'publishing_start',
            'publishing_end',
            'published_by',
            'main_image',
            'images',
        ];

        $reflection = new \ReflectionClass(Page::class);
        $fillableProperty = $reflection->getProperty('fillable');
        $fillableProperty->setAccessible(true);
        
        // Use reflection to get fillable without instantiating the model
        $fillableValue = $fillableProperty->getDefaultValue();

        $this->assertEquals($expectedFillable, $fillableValue);
    }

    #[Test]
    public function page_has_correct_casts(): void
    {
        $expectedCasts = [
            'published' => 'boolean',
            'images' => 'array',
            'publishing_start' => 'datetime',
            'publishing_end' => 'datetime',
        ];

        $reflection = new \ReflectionClass(Page::class);
        $castsProperty = $reflection->getProperty('casts');
        $castsProperty->setAccessible(true);
        
        // Use reflection to get casts without instantiating the model
        $castsValue = $castsProperty->getDefaultValue();

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertArrayHasKey($attribute, $castsValue);
            $this->assertEquals($expectedCast, $castsValue[$attribute]);
        }
    }

    #[Test]
    public function page_sluggable_method_returns_correct_configuration(): void
    {
        // Sluggable method removed from multilingual implementation
        // Test that multilingual helper methods exist instead
        $this->assertTrue(method_exists(Page::class, 'getName'));
        $this->assertTrue(method_exists(Page::class, 'getSlug'));
        $this->assertTrue(method_exists(Page::class, 'getDescription'));
        $this->assertTrue(method_exists(Page::class, 'getContent'));
    }

    #[Test]
    public function page_accessor_methods_exist(): void
    {
        $this->assertTrue(method_exists(Page::class, 'getMainImageUrlAttribute'));
        $this->assertTrue(method_exists(Page::class, 'getMainImageThumbUrlAttribute'));
        $this->assertTrue(method_exists(Page::class, 'getImageUrlsAttribute'));
        $this->assertTrue(method_exists(Page::class, 'getIsCurrentlyPublishedAttribute'));
        $this->assertTrue(method_exists(Page::class, 'getBreadcrumbsAttribute'));
    }

    #[Test]
    public function page_mutator_methods_exist(): void
    {
        $this->assertTrue(method_exists(Page::class, 'setMainImageAttribute'));
        $this->assertTrue(method_exists(Page::class, 'setImagesAttribute'));
    }

    #[Test]
    public function page_scope_methods_exist(): void
    {
        $this->assertTrue(method_exists(Page::class, 'scopePublished'));
        $this->assertTrue(method_exists(Page::class, 'scopeByCategory'));
        $this->assertTrue(method_exists(Page::class, 'scopeRootPages'));
    }

    #[Test]
    public function page_relationship_methods_exist(): void
    {
        $this->assertTrue(method_exists(Page::class, 'category'));
        $this->assertTrue(method_exists(Page::class, 'parent'));
        $this->assertTrue(method_exists(Page::class, 'children'));
        $this->assertTrue(method_exists(Page::class, 'publishedBy'));
    }
}

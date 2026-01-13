<?php

namespace Tests\Unit\Models;

use App\Models\PageCategory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PageCategory model.
 * Tests model structure without Laravel framework dependencies.
 */
class PageCategoryTest extends TestCase
{
    #[Test]
    public function page_category_class_exists(): void
    {
        $this->assertTrue(class_exists(PageCategory::class));
    }

    #[Test]
    public function page_category_extends_eloquent_model(): void
    {
        $this->assertTrue(is_subclass_of(PageCategory::class, \Illuminate\Database\Eloquent\Model::class));
    }

    #[Test]
    public function page_category_uses_expected_traits(): void
    {
        $traits = class_uses_recursive(PageCategory::class);
        
        $this->assertArrayHasKey(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
        $this->assertArrayHasKey(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
        // Note: No longer using Sluggable trait - slugs are generated in the request
    }

    #[Test]
    public function page_category_has_expected_methods(): void
    {
        $this->assertTrue(method_exists(PageCategory::class, 'pages'));
        $this->assertTrue(method_exists(PageCategory::class, 'getName'));
        $this->assertTrue(method_exists(PageCategory::class, 'getSlug'));
        $this->assertTrue(method_exists(PageCategory::class, 'getDescription'));
        // Note: No longer using sluggable method - handled in request
    }

    #[Test]
    public function page_category_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'name',
            'slug',
            'description',
        ];

        $reflection = new \ReflectionClass(PageCategory::class);
        $fillableProperty = $reflection->getProperty('fillable');
        $fillableProperty->setAccessible(true);
        
        // Use reflection to get fillable without instantiating the model
        $fillableValue = $fillableProperty->getDefaultValue();

        $this->assertEquals($expectedFillable, $fillableValue);
    }

    #[Test]
    public function page_category_casts_are_correctly_defined(): void
    {
        $reflection = new \ReflectionClass(PageCategory::class);
        $castsProperty = $reflection->getProperty('casts');
        $castsProperty->setAccessible(true);
        
        // Use reflection to get casts without instantiating the model
        $castsValue = $castsProperty->getDefaultValue();
        
        $this->assertArrayHasKey('name', $castsValue);
        $this->assertArrayHasKey('slug', $castsValue);
        $this->assertArrayHasKey('description', $castsValue);
        $this->assertEquals('array', $castsValue['name']);
        $this->assertEquals('array', $castsValue['slug']);
        $this->assertEquals('array', $castsValue['description']);
    }

    #[Test]
    public function page_category_relationship_methods_exist(): void
    {
        $this->assertTrue(method_exists(PageCategory::class, 'pages'));
    }

    #[Test]
    public function page_category_multilingual_helper_methods_exist(): void
    {
        $this->assertTrue(method_exists(PageCategory::class, 'getName'));
        $this->assertTrue(method_exists(PageCategory::class, 'getSlug'));
        $this->assertTrue(method_exists(PageCategory::class, 'getDescription'));
    }
}

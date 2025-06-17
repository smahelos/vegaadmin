<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use App\Models\StatusCategory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    private Status $status;

    protected function setUp(): void
    {
        parent::setUp();
        $this->status = new Status();
    }

    #[Test]
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('statuses', $this->status->getTable());
    }

    #[Test]
    public function model_has_correct_guarded_attributes(): void
    {
        $this->assertContains('id', $this->status->getGuarded());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $casts = $this->status->getCasts();
        
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    #[Test]
    public function model_uses_correct_traits(): void
    {
        $traits = class_uses($this->status);
        
        $this->assertContains(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    #[Test]
    public function slug_mutator_formats_slug_properly(): void
    {
        $this->status->slug = 'Test Status Name';
        
        $this->assertEquals('test-status-name', $this->status->slug);
    }

    #[Test]
    public function slug_mutator_handles_special_characters(): void
    {
        $this->status->slug = 'Test Status & Name!';
        
        $this->assertEquals('test-status-name', $this->status->slug);
    }

    #[Test]
    public function color_accessor_returns_default_when_null(): void
    {
        $this->status->setRawAttributes(['color' => null]);
        
        $this->assertEquals('bg-gray-100 text-gray-800', $this->status->color);
    }

    #[Test]
    public function color_accessor_returns_actual_value_when_set(): void
    {
        $this->status->setRawAttributes(['color' => 'bg-red-100 text-red-800']);
        
        $this->assertEquals('bg-red-100 text-red-800', $this->status->color);
    }

    #[Test]
    public function color_preview_attribute_generates_html(): void
    {
        $this->status->setRawAttributes([
            'name' => 'Test Status',
            'color' => 'bg-green-100 text-green-800'
        ]);

        $colorPreview = $this->status->color_preview;

        $this->assertStringContainsString('Test Status', $colorPreview);
        $this->assertStringContainsString('bg-green-100 text-green-800', $colorPreview);
        $this->assertStringContainsString('<span', $colorPreview);
    }

    #[Test]
    public function translated_name_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->status, 'getTranslatedNameAttribute'));
    }

    #[Test]
    public function invoices_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->status, 'invoices'));
    }

    #[Test]
    public function category_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->status, 'category'));
    }

    #[Test]
    public function active_scope_exists(): void
    {
        $this->assertTrue(method_exists($this->status, 'scopeActive'));
    }
}
